<?php

namespace App\Http\Controllers;

use App\Events\OrderReviewed;
use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\ApplyRefundRequest;
use App\Http\Requests\CrowdFundingOrderRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SendReviewRequest;
use App\Jobs\CloseOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Services\CartService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['items.product', 'items.productSku'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Order $order)
    {
        $this->authorize('own', $order);
        return view('orders.show', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }


    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));

        // 优惠券
        $coupon = null;
        if ($code = $request->get('coupon_code')) {
            $coupon = CouponCode::where('code', $code)->first();
            if (!$coupon) {
                throw new CouponCodeUnavailableException('优惠券不存在');
            }
        }

        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'), $coupon);
    }

    // 众筹 => 下单
    public function crowdfunding(CrowdFundingOrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));
        $sku = ProductSku::find($request->input('sku_id'));
        $amount = $request->input('amount');

        return $orderService->crowdfunding($user, $address, $sku, $amount);
    }

    // 确认收货
    public function recevied(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED) {
            throw new InvalidRequestException('发货状态不正确');
        }
        // 更新发后状态为已收到
        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);

        return $order;
    }


    // 评分(页面)
    public function review(Order $order)
    {
        $this->authorize('own', $order);
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付, 不可评价');
        }
        return view('orders.review', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }

    // 评分(api)
    public function sendReview(Order $order, SendReviewRequest $request)
    {
        $this->authorize('own', $order);

        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付, 不可评价');
        }
        if ($order->reviewed) {
            throw new InvalidRequestException('该订已评价, 不可重复提交');
        }
        $reviews = $request->input('reviews');
        \DB::transaction(function () use ($reviews, $order) {
           foreach ($reviews as $review) {
               $orderItem = $order->items()->find($review['id']);

               $orderItem->update([
                   'rating'         => $review['rating'],
                   'review'         => $review['review'],
                   'reviewed_at'    => Carbon::now(),
               ]);
           }
           // 将订单标记为已评价
           $order->update(['reviewed' => true]);
           event(new OrderReviewed($order));

        });

        return redirect()->back();
    }

    // 退款
    public function applyRefund(Order $order, ApplyRefundRequest $request)
    {
        $this->authorize('own', $order);

        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付, 不可退款');
        }
        // 众筹商品不允许申请退款
        if ($order->type === Order::TYPE_CROWDFUNDING) {
            throw new InvalidRequestException('众筹订单不支持退款');
        }
        if ($order->refund_status !== Order::REFUND_STATUS_PENDING) {
            throw new InvalidRequestException('该订单已经申请过退款, 请勿重复申请');
        }
        $extra = $order->extra ? : [];
        $extra['refund_reason'] = $request->input('reason');
        $order->update([
            'refund_status' => Order::REFUND_STATUS_APPLIED,
            'extra'         => $extra,
        ]);

        return $order;
    }

    public function wechatRefundNotify()
    {
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        $data = app('wechat_pay')->verify(null, true);

        if (!$order = Order::where('no', $data['out_trade_no'])->first()) {
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS') {
            $order->update([
                'refund_status' =>  Order::REFUND_STATUS_SUCCESS
            ]);
        } else {
            $extra = $order->extra;
            $extra['refund_failed_code'] = $data['refund_status'];
            $order->update([
                'refund_status' => Order::REFUND_STATUS_FAILED,
                'extra' => $extra
            ]);
        }
        return app('wechat_pay')->success();
    }
}
