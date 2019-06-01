<?php

namespace App\Jobs;

use App\Exceptions\InternalException;
use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RefundInstallmentOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        if ($this->order->payment_method !== 'installment'
            || !($this->order->paid_at)
            || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING
        ) {
            return;
        }
        if (!$installment = Installment::query()->where('order_id', $this->order->id)->first()) {
            return;
        }
        // 遍历对应的分期付款的所有还款计划
        foreach ($installment->items as $item) {
            if (!$item->paid_at || in_array($item->refund_status, [
                    InstallmentItem::REFUND_STATUS_PROCESSING,
                    InstallmentItem::REFUND_STATUS_SUCCESS
                ])) {
                continue;
            }
            try {
                $this->refundInstallmentItem($item);
            } catch (\Exception $e) {
                \Log::warning('分期退款失败: ' . $e->getMessage(), [
                    'installment_item_id' => $item->id,
                ]);
                continue;
            }
        }
        $installment->refreshRefundStatus();
    }

    protected function refundInstallmentItem(InstallmentItem $item)
    {
        $refundNo = $this->order->refund_no.'_'.$item->sequence;

        switch ($item->payment_method) {
            case 'wechat':
                app('wechat_pay')->refund([
                    'transaction_id'    =>  $item->payment_no,
                    'total_fee'         =>  $item->total * 100,
                    'refund_fee'        =>  $item->base * 100,
                    'out_refund_no'     =>  $refundNo,
                    'notify_url'        =>  ngrok_url('installments.wechat.refund_notify')
                ]);
                $item->update([
                    'refund_status' =>  InstallmentItem::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                $ret = app('alipay')->refund([
                    'trade_no'          =>  $item->payment_no,
                    'refund_amount'     =>  $item->base,
                    'out_request_no'    =>  $refundNo
                ]);
                // 根据支付宝的文档，如果返回值里有 sub_code 字段说明退款失败
                if ($ret->sub_code) {
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
                    ]);
                } else {
                    // 将订单的退款状态标记为退款成功并保存退款订单号
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            default:
                // 原则上不可能出现，这个只是为了代码健壮性
                throw new InternalException('未知订单支付方式：'.$item->payment_method);
                break;
        }
    }
}
