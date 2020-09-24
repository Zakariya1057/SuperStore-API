<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;

class ParentCategory extends Model
{
    // public $visible = ['id', 'name','child_categories'];
    
    protected $casts = [
        'name' => HTMLDecode::class,
        'description' =>  HTMLDecode::class,
    ];

    public function child_categories() {
        return $this->hasMany('App\ChildCategory','parent_category_id');
    }

}
