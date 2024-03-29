<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRegisterRequest extends FormRequest
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
            'data.name' => 'required|string|max:255',
            'data.email' => 'required|email|max:255',
            'data.region_id' => 'required',
            'data.supermarket_chain_id' => 'sometimes|integer',
            'data.password' => 'required|confirmed|string|min:8|max:255',
            'data.identifier' => '',
            'data.user_token' => '',
            'data.notification_token' => ''
        ];
    }
}
