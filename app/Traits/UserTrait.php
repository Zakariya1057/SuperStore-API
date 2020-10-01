<?php

namespace App\Traits;

use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

trait UserTrait {

    public function validate_field($data, $type,$user_id=false){

        $type_validations = [
            'name' => ['field' => 'name', 'validation' => 'required|string|max:255'],
            'token' => ['field' => 'token', 'validation' => 'required|integer'],
            'email' => ['field' => 'email', 'validation' => 'required|email|max:255'],
            'password' => ['field' => 'password', 'validation' => 'required|string'],
            'edit_password' => ['field' => 'password', 'validation' => 'required|string|min:8|confirmed'],
            'new_password' => ['field' => 'password', 'validation' => 'required|string|min:8|confirmed'],
        ];
        
        if(!key_exists($type,$type_validations)){
            return response()->json(['data' => ['error' => 'Unknown Type: '. $type]], 422);
        }

        $validation = $type_validations[$type]['validation'];
        $field = $type_validations[$type]['field'];

        if(!key_exists($field, $data)){
            return response()->json(['data' => ['error' => "The $field field must be included."]], 422);
        } else {
            $validator = Validator::make($data, [$field => $validation]);
            if($validator->fails()) {
                return response()->json(['data' => ['error' => $validator->errors()->get($field)[0]]], 422);
            }
        }
        
        if($type == 'edit_password'){
            // Make sure passwords match up correctly
            if(!key_exists('current_password', $data)){
                return response()->json(['data' => ['error' => "The current password field must be included."]], 422);
            }

            if($data['current_password'] == ''){
                return response()->json(['data' => ['error' => "Current password required"]], 422);
            }

            $current_password = $data['current_password'];
            $new_password = $data['password'];

            $password_results = User::where('id', $user_id)->select('password')->get()->first();
            if(!$password_results){
                return response()->json(['data' => ['error' => "No User Found."]], 422);
            }

            if($current_password == $new_password){
                return response()->json(['data' => ['error' => "New password must be different to current password."]], 422);
            }

            if (!Hash::check($current_password, $password_results->password)) {
                return response()->json(['data' => ['error' => "Incorrect current password."]], 422);
            }

        }

    }


}

?>