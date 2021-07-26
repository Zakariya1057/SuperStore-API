<?php

namespace App\Models;

use App\Casts\HTMLDecode;
use Illuminate\Database\Eloquent\Model;

class GrandParentCategory extends Model
{
    protected $casts = [
        'name' => HTMLDecode::class,
        'enabled' => 'Bool'
    ];

    public function parent_categories() {
        return $this->hasMany('App\Models\ParentCategory','parent_category_id');
    }
}
