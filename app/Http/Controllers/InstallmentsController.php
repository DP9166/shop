<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installments = Installment::query()
            ->where('user_id', $request->user()->id)
            ->paginate(10);

        return view('installments.index', ['installments' => $installments]);
    }

    public function show(Installment $installment)
    {
        $this->authorize('own', $installment);
        // 取出当前分期付款所有的还款计划, 并按还款顺序排序
        $items = $installment->items()->orderBy('sequence')->get();
        return view('installments.show', [
           'installment'    =>  $installment,
           'items'          =>  $items,
           'nextItem'       =>  $items->where('paid_at', null)->first(),
        ]);
    }

    // 支付
    public function payByAlipay(Installment $installment)
    {
        if ($installment->order->closed) {
            throw new InvalidRequestException('对应的商品订单已被关闭');
        }
        if ($installment->status === Installment::STATUS_FINISHED) {
            throw new InvalidRequestException('该分期订单已被结清');
        }
        if (!$nextItem = $installment->items()->whereNull('paid_at')->orderBy('sequence')->first()) {
            throw new InvalidRequestException('该分期订单已结清');
        }

        return app('alipay')->web([
            'out_trade_no'  =>  $installment->no . '_' . $nextItem->sequence,
            'total_amount'  =>  $nextItem->total,
            'subject'       =>  '支付 Laravel Shop 的分期订单: '.$installment->no,
            'notify_url'    =>  ngrok_url('installments.alipay.notify'),
            'return_url'    =>  route('installments.alipay.return'),
        ]);
    }

    public function alipayReturn()
    {
        try {
            app('alipay')->verity();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }
        return view('pages.success', ['msg' =>  '付款成功']);
    }

    public function alipayNotify()
    {
        $data = app('alipay')->verify();

        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        // 还原还款是哪个分期付款的哪个还款计划
        list($no, $sequence) = explode('_', $data->out_trade_no);
        // 根据分期流水号查询对应的分期记录
        if (!$installment = Installment::where('no', $no)->first()) {
            return 'fail';
        }
        // 根据还款计划编号查询讯对应的还款计划
        if (!$item = $installment->items()->where('sequence', $sequence)->first()) {
            return 'fail';
        }
        if ($item->paid_at) {
            return app('alipay')->success();
        }

        \DB::transcation(function () use ($data, $no, $installment, $item) {
           $item->update([
               'paid_at'            =>  Carbon::now(), // 支付时间
               'payment_method'     =>  'alipay',   // 支付方式
               'payment_no'         =>  $data->trade_no, // 支付宝订单号
           ]);

           if ($item->sequence === 0) {
               $installment->update(['status'  =>  Installment::STATUS_PEPAYING]);
               $installment->order->update([
                   'paid_at'        => Carbon::now(),
                   'payment_method' => 'installment',
                   'payment_no'     => $no,
               ]);
               event(new OrderPaid($installment->order)); // 触发商品订单已支付的事件
           }

           if ($item->sequencec === $installment->count - 1) {
               $installment->update(['status'   => Installment::STATUS_FINISHED]);
           }
        });
        return app('alipay')->success();
    }

}
