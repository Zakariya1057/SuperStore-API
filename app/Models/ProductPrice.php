<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Casts\CurrencyCast;

class ProductPrice extends Model
{
    public $casts = [
        'price' => 'double',
        'currency' => CurrencyCast::class,

        'is_on_sale' => 'boolean',

        'old_price' => 'double',

        'sale_ends_at' => 'datetime:Y-m-d H:i:s',
    ];

}
