<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Order;


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
    // 服务器回调
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
        return app('alipay')->success();
    }

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
}
