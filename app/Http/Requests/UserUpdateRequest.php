<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
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
            'data.name' => [],
            'data.email' => [],
            'data.region_id' => [],
            'data.store_type_id' => [],
            'data.password' => [],
            'data.password_confirmation' => [],
            'data.current_password' => [],
            'data.type' => [],
            'data.send_notifications' => [],
            'data.notification_token' =>  []
        ];
    }
}
