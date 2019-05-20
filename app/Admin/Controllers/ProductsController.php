<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ProductsController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('商品列表')
            ->body($this->grid());
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑商品')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('创建商品')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Product);
        // 使用With 来预加载, 减少sql 查询
        $grid->model()->with(['category']);

        $grid->id('Id');
        $grid->title('商品名称');
        $grid->column('category.name', '类目');
        $grid->on_sale('已上架')->display(function ($value) {
            return $value ? '是' : '否';
        });
        $grid->rating('评分');
        $grid->sold_count('销量');
        $grid->review_count('评论数');
        $grid->price('价格');
        $grid->created_at('创建时间');

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->tools(function ($tools) {
           $tools->batch(function ($batch) {
               $batch->disableDelete();
           }) ;
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Product::findOrFail($id));

        $show->id('Id');
        $show->title('Title');
        $show->description('Description');
        $show->image('Image');
        $show->on_sale('On sale');
        $show->rating('Rating');
        $show->sold_count('Sold count');
        $show->review_count('Review count');
        $show->price('Price');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Product);

        $form->text('title', '商品名称')->rules('required');

        $form->select('category_id', '类目')->options(function ($id) {
           $category = Category::find($id);
           if ($category) {
               return [$category->id => $category->full_name];
           }
        })->ajax('/admin/api/categories?is_directory=0');


        $form->editor('description', '商品描述')->rules(  'required');
        $form->image('image', '封面图片')->rules('required|image');
        $form->radio('on_sale', '上架')->options(['1'=>'是', '0'=>'否'])->default('0');

        $form->hasMany('skus', 'SKU列表', function (Form\NestedForm $from) {
            $from->text('title', 'SKU 名称')->rules('required');
            $from->text('description', 'SKU 描述')->rules('required');
            $from->text('price', '单价')->rules('required|numeric|min:0.01');
            $from->text('stock', '剩余库存')->rules('required|integer|min:0');
        });

        // 定义事件回调, 当模型即将保存时会触发这个回调
        $form->saving(function (Form $form) {
           $form->model()->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price') ?: 0;
        });

        return $form;
    }
}
