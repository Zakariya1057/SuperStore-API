<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordNewPasswordRequest extends FormRequest
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
            'data.code' => 'required|integer',
            'data.email' => 'required|email|max:255',
            'data.password' => 'required|confirmed|string|min:8|max:255',
            'data.notification_token' => ''
        ];
    }
}
