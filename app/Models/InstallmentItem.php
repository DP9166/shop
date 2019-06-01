<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InstallmentItem extends Model
{
    const REFUND_STATUS_PENDING     =   'pending';
    const REFUND_STATUS_PROCESSING  =   'processing';
    const REFUND_STATUS_SUCCESS     =   'success';
    const REFUND_STATUS_FAILED       =   'failed';


    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING     =>  '未还款',
        self::REFUND_STATUS_PROCESSING  =>  '还款中',
        self::REFUND_STATUS_SUCCESS     =>  '还款成功',
        self::REFUND_STATUS_FAILED       =>  '还款失败',
    ];

    protected $fillable = [
        'sequence', 'base', 'fee', 'fine', 'due_date', 'paid_at',
        'payment_method',   'payment_no',   'refund_status'
    ];
    protected $dates = ['due_date', 'paid_at'];

    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }

    // 当前还款计划需还款总金额
    public function getTotalAttribute()
    {
        $total = big_number($this->base)->add($this->fee);
        if (!is_null($this->fine)) {
            $total->add($total, $this->fine);
        }
        return $total->getValue();
    }

    // 查看是否已经逾期
    public function getIsOverdueAttribute()
    {
        return Carbon::now()->gt($this->due_date);
    }
}
