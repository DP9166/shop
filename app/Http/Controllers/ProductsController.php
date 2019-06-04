<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
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

        $params = [
            'index' =>  'products',
            'type'  =>  '_doc',
            'body'  =>  [
                'from'  =>  ($page - 1) * $perPage,
                'size'  =>  $perPage,
                'query' =>  [
                    'bool'  =>  [
                        'filter'    =>  [
                            ['term' =>  ['on_sale'  =>  true]],
                        ]
                    ]
                ]
            ]
        ];


        if ($order = $request->input('order', '')) {
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    $params['body']['sort'] = [[$m[1]   =>  $m[2]]];
                }
            }
        }

        if ($search = $request->input('search', '')) {
            $keywords = array_filter(explode(' ', $search));

            $params['body']['query']['bool']['must'] = [];

            foreach ($keywords as $keyword) {
                $params['body']['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query' => $keyword,
                        'fields' => [
                            'title^3',
                            'long_title^2',
                            'category^2',
                            'description',
                            'skus_title',
                            'skus_description',
                            'properties_value'
                        ]
                    ]
                ];
            }
        }

        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            // 如果这是一个父类目
            if ($category->is_directory) {
                // 筛选出该父类目中所有子类目的商品
                $params['body']['query']['bool']['filter'][] = [
                    'perfix'    =>  ['category_path'  => $category->path.$category->id. '-'],
                ];
            } else {
                $params['body']['query']['bool']['filter'][] = ['term'  =>  ['category_id' =>  $category->id]];
            }
        }

        $result = app('es')->search($params);

        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();
        $products = Product::query()->whereIn('id', $productIds)
            ->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $productIds)))->get();

        $pager = new LengthAwarePaginator($products, $result['hits']['total'], $perPage, $page, [
            'path'  =>  route('products.index', false), // 手动构建分页的 url
        ]);


        return view('products.index', [
            'products'=>$pager,
            'filters' => [
                'search' => $search,
                'order' => $order,
            ],
            // 等价于 isset($category) ? $category : null
            'category' => $category ?? null,
            'categoryTree'  => $categoryService->getCategoryTree()
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
