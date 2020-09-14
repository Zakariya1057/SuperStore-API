<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GroceryListItem extends Model
{
    public $casts = [
        'total_price' => 'double',
        'ticked_off' => 'Bool',
        'created_at' => 'datetime:d F Y',
    ];
}
