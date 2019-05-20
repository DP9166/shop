<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'is_directory', 'level', 'path'];
    protected $casts = [ 'is_directory'  => 'boolean' ];

    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub

        // 监听 Category 的创建事件, 用于初始化 path 和 level 字段值
        static::creating(function (Category $category) {
            if (is_null($category->parent_id)) {
                // 将层级设为0
                $category->level = 0;
                // 将 path 设为 -
                $category->path = '-';
            } else {
                $category->level = $category->parent->level + 1;

                $category->path = $category->parent->path . $category->parent_id . '-';
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // 获取所有祖先类目并按层级排序
    public function getAncestorsAttribute()
    {
        return Category::query()
            ->whereIn('id', $this->path_ids)
            ->orderBy('level')->get();
    }

    // 获取以 - 为分割的所有祖先名称以及当前类目的名称
    public function getFullNameAttribute()
    {
        return $this->ancestors
            ->pluck('name') // 取出所有祖先类目的 name 字段作为一个数组
            ->push($this->name) // 将当前类目的 name 字段值加到数组的末尾
            ->implode(' - '); // 用 - 符号将数组装成一个字符串
    }
}
