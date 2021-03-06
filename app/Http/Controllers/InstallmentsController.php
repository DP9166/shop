<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installments = Installment::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
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
            app('alipay')->verify();
        } catch (\Exception $e) {
            Log::info($e);
            return view('pages.error', ['msg' => '数据不正确']);
        }
        return view('pages.success', ['msg' => '付款成功']);
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
        \DB::transaction(function () use ($data, $no, $installment, $item) {
            $item->update([
                'paid_at'            =>  Carbon::now(), // 支付时间
                'payment_method'     =>  'alipay',   // 支付方式
                'payment_no'         =>  $data->trade_no, // 支付宝订单号
            ]);
            if ($item->sequence === 0) {
                $installment->update(['status'  =>  Installment::STATUS_REPAYING]);
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
    public function wechatRefundNotify(Request $request)
    {
        // 给微信的失败响应
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        // 校验微信回调参数
        $data = app('wechat_pay')->verify(null, true);
        // 根据单号拆解出对应的商品退款单号及对应的还款计划序号
        list($no, $sequence) = explode('_', $data['out_refund_no']);
        $item = InstallmentItem::query()
            ->whereHas('installment', function ($query) use ($no) {
                $query->whereHas('order', function ($query) use ($no) {
                    $query->where('refund_no', $no); // 根据订单表的退款流水号找到对应还款计划
                });
            })
            ->where('sequence', $sequence)
            ->first();
        // 没有找到对应的订单，原则上不可能发生，保证代码健壮性
        if (!$item) {
            return $failXml;
        }
        // 如果退款成功
        if ($data['refund_status'] === 'SUCCESS') {
            // 将还款计划退款状态改成退款成功
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
            ]);
            $item->installment->refreshRefundStatus();
        } else {
            // 否则将对应还款计划的退款状态改为退款失败
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
            ]);
        }
        return app('wechat_pay')->success();
    }
}
