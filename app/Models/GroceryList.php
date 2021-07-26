<?php

namespace App\Models;
use App\Casts\HTMLDecode;
use App\Casts\CurrencyCast;

use Illuminate\Database\Eloquent\Model;

class GroceryList extends Model
{
    protected $fillable = ['name', 'currency', 'identifier', 'status', 'user_id', 'supermarket_chain_id'];

    public $casts = [
        'name' => HTMLDecode::class,
        'currency' => CurrencyCast::class,

        'total_price' => 'double',
        'old_total_price' => 'double',

        'supermarket_chain_id' => 'integer',
        
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
