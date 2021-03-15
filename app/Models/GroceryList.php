<?php

namespace App\Models;
use App\Casts\HTMLDecode;
use App\Casts\CurrencyCast;

use Illuminate\Database\Eloquent\Model;

class GroceryList extends Model
{
    protected $fillable = ['name', 'currency', 'identifier', 'status', 'user_id', 'store_type_id'];

    public $casts = [
        'name' => HTMLDecode::class,
        'currency' => CurrencyCast::class,

        'total_price' => 'double',
        'old_total_price' => 'double',

        'store_type_id' => 'integer',
        
        'created_at' => 'datetime:d F Y',
        'updated_at' => 'datetime:d F Y',
    ];
}
