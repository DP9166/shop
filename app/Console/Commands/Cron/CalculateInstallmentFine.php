<?php

namespace App\Console\Commands\Cron;

use App\Models\Installment;
use App\Models\InstallmentItem;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateInstallmentFine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:calculate-installment-fine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算分期付款逾期费';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       InstallmentItem::query()
            ->with(['installment'])
            ->whereHas('installment', function ($query) {
                $query->where('status', Installment::STATUS_REPAYING);
            })
            ->where('due_date', '<=', Carbon::now())
            ->whereNull('paid_at')
            ->chunkById(1000, function ($items) {
                foreach ($items as $item) {
                    // 逾期天数
                    $overdueDays = Carbon::now()->diffInDays($item->due_date);
                    // 本金与手续费之和
                    $base = big_number($item->base)->add($item->fee)->getValue();
                    // 计算逾期费
                    $fine = big_number($base)->multiply($overdueDays)
                        ->multiply($item->installment->fine_rate)
                        ->divide(100)->getValue();
                    // 避免出现逾期费比本金和手续费之和要高
                    $fine = big_number($fine)->compareTo($base) === 1 ? $base : $fine;
                    $item->update(['fine' => $fine]);
                }
            });
    }

}
