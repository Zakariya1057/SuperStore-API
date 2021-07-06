<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchProductResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'data.query' => 'required',
            'data.type'  => 'required',

            'data.sort' => '', // Rating, Price, Sugar, etc.
            'data.order' => '', // asc/desc

            'data.dietary' => '', // Halal, Vegetarian
            'data.product_group' => '',
            'data.brand' => '',
            'data.promotion' => '',

            'data.availability_type' => '',

            'data.text_search' => '',

            'data.region_id' => 'required',
            'data.region_id' => '',
            'data.supermarket_chain_id' => 'required',
        ];
    }
}
