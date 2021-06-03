<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryProductRequest extends FormRequest
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
            'data.availability_type' => '',

            'data.sort' => '',
            'data.order' => '',
            'data.dietary' => '',
            'data.brand' => '',
            'data.promotion' => '',
            'data.product_group' => '',

            // 'data.region_id' => 'required',
            'data.region_id' => '',
        ];
    }
}
