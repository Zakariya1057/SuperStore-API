<?php

namespace App\Models;

use App\Casts\Image;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $visible = ['name', 'size', 'product_id'];
    
    public $casts = [
        'name' => Image::class,
    ];
}
