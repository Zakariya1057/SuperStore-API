<?php

namespace App\Models;
use App\Casts\HTMLDecode;

use Illuminate\Database\Eloquent\Model;

class GroceryList extends Model
{
    protected $fillable = ['name','identifier', 'status', 'user_id', 'store_type_id'];

    public $casts = [
        'name' => HTMLDecode::class,
        'total_price' => 'double',
        'old_total_price' => 'double',

        'store_type_id' => 'integer',
        
        'created_at' => 'datetime:d F Y',
        'updated_at' => 'datetime:d F Y',
    ];
}
