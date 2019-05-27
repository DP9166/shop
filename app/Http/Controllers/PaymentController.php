<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Validation\Rule;


class PaymentController extends Controller
{

    // 前端页面的回调
    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }
        return view('pages.success', ['msg' => '付款成功']);
    }

    // 服务器回调(微信)
    public function wechatNotify()
    {
        $data = app('wechat_pay')->verify();

        $order = Order::where('no', $data->out_trade_no)->first();
        if (!$order) return 'fail'; // 支付一个不存在的订单
        if ($order->paid_at) return app('wechat_pay')->success(); // 该订单已经被支付

        $order->update([
            'paid_at'           => Carbon::now(),
            'payment_method'    => 'wechat', // 支付方式
            'payment_no'        => $data->transaction_id, // 支付订单号
        ]);

        $this->afterPaid($order);
        return app('wechat_pay')->success();
    }

    // 服务器回调(支付宝)
    public function alipayNotify()
    {
        $data = app('alipay')->verify();
       if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
           return app('alipay')->success();
       }
       $order = Order::where('no', $data->out_trade_no)->first();
       if (!$order) return 'fail'; // 支付一个不存在的订单
       if ($order->paid_at) return app('alipay')->success(); // 该订单已经被支付

        $order->update([
            'paid_at'           => Carbon::now(),
            'payment_method'    => 'alipay', // 支付方式
            'payment_no'        => $data->trade_no, // 支付订单号
        ]);

        $this->afterPaid($order);
        return app('alipay')->success();
    }

    // 微信调起页面
    public function payByWechat(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        $wechatOrder = app('wechat_pay')->web([
            'out_trade_no'  => $order->no, // 订单编号
            'total_fee'  => $order->total_amount * 100, // 订单金额
            'body'       => '支付 Laravel Shop 的订单'. $order->no, // 订单标题
        ]);

        $qrCode = new QrCode($wechatOrder->code_url);
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    // 支付宝调起页面
    public function payByAlipay(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        return app('alipay')->web([
            'out_trade_no'  => $order->no, // 订单编号
            'total_amount'  => $order->total_amount, // 订单金额
            'subject'       => '支付 Laravel Shop 的订单'. $order->no, // 订单标题
        ]);
    }

    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }

    public function payByInstallment(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        if ($order->total_amount < config('app.min_installment_amount')) {
            throw new InvalidRequestException('订单金额低于最低分期金额');
        }
        $this->validate($request, [
            'count' =>  ['required',Rule::in(array_keys(config('app.installment_fee_rate')))],
            ]);
        Installment::query()->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();
        $count = $request->input('count');
        // 创建一个新的分期付款对象
        $installment = new Installment([
            'total_amount'  =>  $order->total_amount,
            'count'         =>  $count,
            'fee_rate'      =>  config('app.installment_fee_rate')[$count],
            'fine_rate'     =>  config('app.installment_fine_rate')
        ]);
        $installment->user()->associate($request->user());
        $installment->order()->associate($order);
        $installment->save();


        // 第一期的还款截止日期为明天凌晨0点
        $dueDate = Carbon::tomorrow();
        // 计算每一期本金
        $base = big_number($order->total_amount)->divide($count)->getValue();
        // 计算每一期手续费
        $fee = big_number($base)->multiply($installment->fee_date)->divide(100)->getValue();

        // 根据用户选择的还款期数, 创建对应的还款计划
        for ($i = 0; $i < $count; $i++) {
            if ($i === $count -1) {
                $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count-1));
            }
            $installment->items()->create([
                'sequence'  =>  $i,
                'base'      =>  $base,
                'fee'       =>  $fee,
                'due_date'  =>  $dueDate,
            ]);
            // 还款截止日期加30天
            $dueDate = $dueDate->copy()->addDays(30);
        }
        return $installment;
    }
}
