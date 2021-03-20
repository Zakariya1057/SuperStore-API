<?php

namespace App\Casts;

use App\Services\PromotionService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

// class PromotionCalculator implements CastsAttributes
// {
//     private $promotion_service;

//     function __construct(){
//         $this->promotion_service = new PromotionService();
//     }

//     /**
//      * Cast the given value.
//      *
//      * @param  \Illuminate\Database\Eloquent\Model  $model
//      * @param  string  $key
//      * @param  mixed  $value
//      * @param  array  $attributes
//      * @return mixed
//      */
//     public function get($model, $key, $value, $attributes)
//     {
//         return $this->promotion_service->details($model->promotion_id, $value, $model->store_type_id);
//     }

//     /**
//      * Prepare the given value for storage.
//      *
//      * @param  \Illuminate\Database\Eloquent\Model  $model
//      * @param  string  $key
//      * @param  array  $value
//      * @param  array  $attributes
//      * @return mixed
//      */
//     public function set($model, $key, $value, $attributes)
//     {
//         return $value;
//     }
// }
