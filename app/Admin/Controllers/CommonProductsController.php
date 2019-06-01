<?php


namespace App\Admin\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

abstract class CommonProductsController extends Controller
{
    abstract public function getProductType();

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header(Product::$typeMap[$this->getProductType()]. '列表')
            ->body($this->grid());
    }

    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑'. Product::$typeMap[$this->getProductType()])
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->header('创建'. Product::$typeMap[$this->getProductType()])
            ->body($this->form());
    }

    protected function grid()
    {
        $grid = new Grid(new Product);

        // 只展示 type 为众筹类型的商品
        $grid->model()->where('type', $this->getProductType())->orderBy('id', 'desc');

        $grid->id('ID')->sortable();
        $grid->title('商品名称');
        $grid->on_sale('已上架')->display(function ($value) {
            return $value ? '是' : '否';
        });
        $grid->price('价格');

        $this->customGrid($grid);

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        return $grid;
    }

    abstract protected function customGrid(Grid $grid);

    protected function form()
    {
        $form = new Form(new Product);

        // 在表单中添加一个名为 type，值为 Product::TYPE_CROWDFUNDING 的隐藏字段
        $form->hidden('type')->value($this->getProductType());
        $form->text('title', '商品名称')->rules('required');
        $form->select('category_id', '类目')->options(function ($id) {
            $category = Category::find($id);
            if ($category) {
                return [$category->id => $category->full_name];
            }
        })->ajax('/admin/api/categories?is_directory=0');
        $form->image('image', '封面图片')->rules('required|image');
        $form->editor('description', '商品描述')->rules('required');
        $form->radio('on_sale', '上架')->options(['1' => '是', '0' => '否'])->default('0');
        // 自定义方法
        $this->customForm($form);


        $form->hasMany('skus', '商品 SKU', function (Form\NestedForm $form) {
            $form->text('title', 'SKU 名称')->rules('required');
            $form->text('description', 'SKU 描述')->rules('required');
            $form->text('price', '单价')->rules('required|numeric|min:0.01');
            $form->text('stock', '剩余库存')->rules('required|integer|min:0');
        });

        $form->hasMany('properties', '商品属性', function (Form\NestedForm $form) {
            $form->text('name', '属性名')->rules('required');
            $form->text('value', '属性值')->rules('required');
        });

        $form->saving(function (Form $form) {
            $form->model()->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price');
        });

        return $form;
    }

    abstract protected function customForm(Form $form);
}