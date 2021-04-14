<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommended extends Model
{
    public function product() {
        return $this->belongsTo('App\Models\Product','recommended_product_id')->where('products.enabled', 1)->limit(5);
    }
}
