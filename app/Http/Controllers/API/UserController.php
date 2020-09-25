<?php

namespace App\Http\Controllers\API;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function register(Request $request){

        $validated_data = $request->validate([
            'data.name' => [],
            'data.email' => [],
            'data.password' => [],
        ]);

        $data = $validated_data['data'];

        $nameError = $this->validate_field($data,'name');
        $emailError = $this->validate_field($data,'email');
        $passwordError = $this->validate_field($data,'new_password');

        if($nameError || $passwordError || $emailError){
            return $nameError ?? $passwordError ?? $emailError;
        }

        if( User::where('name', $data['name'])->exists() ){
            return response()->json(['data' => ['error' => 'Name belongs to another user.']], 422);
        }

        if( User::where('email', $data['email'])->exists() ){
            return response()->json(['data' => ['error' => 'Email address belongs to another user.']], 422);
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json(['data' => ['status' => 'sucess']]);
    }

    public function login(Request $request){

        $validated_data = $request->validate([
            'data.email' => [],
            'data.password' => []
        ]);
        
        $data = $validated_data['data'];

        $emailError = $this->validate_field($data,'email');
        $passwordError = $this->validate_field($data,'password');

        if($emailError || $passwordError){
            return $emailError ?? $passwordError;
        }

        $password_results = User::where('email', $data['email'])->select('password')->get()->first();

        if(!$password_results){
            return response()->json(['data' => ['error' => 'Email address doesn\'t belongs to any user.']], 404);
        }

        if (Hash::check($data['password'], $password_results->password)) {
            return response()->json(['data' => ['token' => 'token']]);
        } else {
            return response()->json(['data' => ['error' => 'Wrong password.']], 404);
        }

    }

    public function update(Request $request){

        $user_id = 1;

        $validated_data = $request->validate([
            'data.email' => [],
            'data.name' => [],
            'data.password' => [],
            'data.password_confirmation' => [],
            'data.current_password' => [],
            'data.type' => [],
        ]);

        $data = $validated_data['data'];

        if(!key_exists('type',$data)){
            return response()->json(['data' => ['error' => 'Type required.']], 422);
        }

        $type = $data['type'];
        $value = $data[ $data['type'] ];

        if($type == 'password'){
            $type = 'new_password';
        }

        $error = $this->validate_field($data,$type);
        if($error){
            return $error;
        }

        if($type == 'new_password'){
           $value  = Hash::make($value);
        }

        User::where('id',$user_id)->update([$data['type'] => $value ]);

        return response()->json(['data' => ['status' => 'sucess']]);

    }

    public function validate_field($data, $type){

        $user_id = 1;

        $type_validations = [
            'name' => ['field' => 'name', 'validation' => 'required|string|max:255'],
            'email' => ['field' => 'email', 'validation' => 'required|email|max:255'],
            'password' => ['field' => 'password', 'validation' => 'required|string'],
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

        if($type == 'new_password'){
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
