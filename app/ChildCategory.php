<?php

namespace App;

use App\Casts\HTMLDecode;
use Illuminate\Database\Eloquent\Model;

class ChildCategory extends Model
{
    protected $casts = [
        'name' => HTMLDecode::class
    ];

    public function products() {
        return $this->hasMany('App\Product','parent_category_id');
    }
}
