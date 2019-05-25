<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class CrowdfundingProductsController extends CommonProductsController
{

    public function getProductType()
    {
        return Product::TYPE_CROWDFUNDING;
    }

    protected function customGrid(Grid $grid)
    {
        $grid->column('crowdfunding.target_amount', '目标金额');
        $grid->column('crowdfunding.end_at', '结束时间');
        $grid->column('crowdfunding.total_amount', '目前金额');
//        $grid->column('crowdfunding.status', ' 状态')->display(function ($value) {
//            return CrowdfundingProduct::$statusMap[$value];
//        });
    }

    protected function customForm(Form $form)
    {
        $form->text('crowdfunding.target_amount', '众筹目标金额')->rules('required|numeric|min:0.01');
        $form->datetime('crowdfunding.end_at', '众筹结束时间')->rules('required|date');
    }
}