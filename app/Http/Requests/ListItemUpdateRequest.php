<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListItemUpdateRequest extends FormRequest
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
            'data.quantity' => 'required',
            'data.ticked_off' => 'required'
        ];
    }
}
