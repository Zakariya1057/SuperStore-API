<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\Image;

class Recommended extends Model
{
    public function product() {
        return $this->belongsTo('App\Product','recommended_product_id')->limit(5);
    }
}
