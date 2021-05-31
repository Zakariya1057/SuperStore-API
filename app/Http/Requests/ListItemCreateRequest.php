<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListItemCreateRequest extends FormRequest
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
            'data.product_id' => 'required',
            'data.parent_category_id' => 'required'
        ];
    }
}
