<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\SearchBuilders\ProductSearchBuilder;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    //
    public function index(Request $request, CategoryService $categoryService)
    {

        $page = $request->input('page', 1);
        $perPage = 16;

        $builder = (new ProductSearchBuilder())->onSale()->paginate($perPage, $page);

        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            $builder->category($category);
        }

        if ($search = $request->input('search', '')) {
            $keywords = array_filter(explode(' ', $search));
            $builder->keywords($keywords);
        }



        if ($order = $request->input('order', '')) {
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }


        if ($search || isset($category)) {
            $builder->aggregateProperties();
        }

        $propertyFilters = [];
        if ($filterString = $request->input('filters')) {
            $filterArray = explode('|', $filterString);

            foreach ($filterArray as $filter) {
                list($name, $value) = explode(':', $filter);

                $propertyFilters[$name] = $value;
                $builder->propertyFilter($name, $value);

            }
        }

        $result = app('es')->search($builder->getParams());

        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();
        $products = Product::query()->whereIn('id', $productIds)
            ->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $productIds)))->get();

        $pager = new LengthAwarePaginator($products, $result['hits']['total'], $perPage, $page, [
            'path'  =>  route('products.index', false), // 手动构建分页的 url
        ]);

        $properties = [];
        if (isset($result['aggregations'])) {
            $properties = collect($result['aggregations']['properties']['properties']['buckets'])
                ->map(function ($bucket) {
                    return [
                        'key'        =>  $bucket['key'],
                        'values'     =>  collect($bucket['value']['buckets'])->pluck('key')->all()
                    ];
                })->filter(function ($property) use ($propertyFilters) {
                    // 过滤只剩下一个值 或者 已经在筛选条件里的属性
                    return count($property['values']) > 1 &&
                        !isset($propertyFilters[$property['key']]);
                });
        }


        return view('products.index', [
            'products'=>$pager,
            'filters' => [
                'search' => $search,
                'order' => $order,
            ],
            // 等价于 isset($category) ? $category : null
            'category'          => $category ?? null,
            'categoryTree'      => $categoryService->getCategoryTree(),
            'properties'        =>  $properties,
            'propertyFilters'   =>  $propertyFilters
        ]);
    }

    public function show(Product $product, Request $request)
    {
        if (!$product->on_sale) {
            throw new InvalidRequestException('商品未上架');
        }
        $favored = false;

        if ($user = $request->user()) {
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku'])
            ->where('product_id', $product->id)
            ->whereNotNull('reviewed_at')
            ->orderBy('reviewed_at', 'desc')
            ->limit(10)->get();

        return view('products.show', ['product' => $product, 'favored' => $favored, 'reviews' => $reviews]);
    }


    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);
        return view('products.favorites', ['products' => $products]);
    }

    public function favor(Product $product, Request $request)
    {
        $user = $request->user();
        if ($user->favoriteProducts()->find($product->id)) {
            return [];
        }

        $user->favoriteProducts()->attach($product);

        return [];
    }

    public function disfavor(Product $product, Request $request)
    {
        $user = $request->user();
        $user->favoriteProducts()->detach($product);

        return [];
    }
}
