<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class HTMLDecode implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, $key, $value, $attributes)
    {
        return is_null($value) ? $value : html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        $value = str_replace('\n', "\n", $value);
        $value = strip_tags($value);
        $value = preg_replace( "/\r/", '', $value);
        
        return htmlentities($value, ENT_QUOTES,'UTF-8', false);
    }
}
