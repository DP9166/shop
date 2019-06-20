<?php

namespace App\Services;


use App\Models\Product;
use App\SearchBuilders\ProductSearchBuilder;

class ProductService
{
    public function getSimilarProductsIds(Product $product, $amount)
    {
        if (count($product->properties) === 0) return [];

        // 创建一个查询构造器, 只搜索上架的商品, 取搜索结果的前4个商品
        $builder = (new ProductSearchBuilder())->onSale()->paginate($amount, 1);
        // 遍历当前商品属性
        foreach ($product->properties as $property) {
            $builder->propertyFilter($property->name, $property->value, 'should');
        }
        // 设置匹配最少一半属性
        $builder->minShouldMatch(ceil(count($product->properties) / 2));
        $params = $builder->getParams();
        // 排除当前ID
        $params['body']['query']['bool']['must_not'] = [['term' =>  ['_id'  =>  $product->id]]];

        $result = app('es')->search($params);

        return collect($result['hits']['hits'])->pluck('_id')->all();
    }
}