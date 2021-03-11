<?php

namespace App\Models;

use App\Casts\HTMLDecode;
use Illuminate\Database\Eloquent\Model;

class GrandParentCategory extends Model
{
    public $visible = ['id', 'name','parent_categories', 'store_type_id'];

    protected $casts = [
        'name' => HTMLDecode::class
    ];

    public function parent_categories() {
        return $this->hasMany('App\ParentCategory','parent_category_id');
    }
}
