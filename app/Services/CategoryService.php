<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    /*
     * $parentId 代表获取子类目中的父类目ID, null 代表汇过去所有根类目
     * $allCategories 参数代表数据库中所有的类目, 如果是null 就要去查数据库
     * */
    public function getCategoryTree($parentId = null, $allCategories = null)
    {
        if (is_null($allCategories)) {
            $allCategories = Category::all();
        }

        return $allCategories->where('parent_id', $parentId)
            ->map(function (Category $category) use ($allCategories) {
                $data = ['id' => $category->id, 'name' => $category->name];
                if (!$category->is_directory) {
                    return $data;
                }
                // 否则调用本方法, 将返回值放入children 字段中
                $data['children'] = $this->getCategoryTree($category->id, $allCategories);

                return $data;
            });
    }
}