<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nutrition extends Model
{
    protected $table = 'nutritions';

    public function childNutritions() {
        return $this->hasMany('App\Models\ChildNutrition', 'parent_nutrition_id', 'id');
    }

}
