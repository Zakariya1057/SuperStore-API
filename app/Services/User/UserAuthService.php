<?php

namespace App\Services\User;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use \Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserAuthService {
    public function validate_field($data, $type,$user_id=false){

        $type_validations = [
            'name' => ['field' => 'name', 'validation' => 'required|string|max:255'],
            'supermarket_chain_id' => ['field' => 'supermarket_chain_id', 'validation' => 'required|integer'],
            'region_id' => ['field' => 'region_id', 'validation' => 'required|integer'],
            'notification_token' => ['field' => 'notification_token', 'validation' => 'required|string|max:255'],
            'code' => ['field' => 'code', 'validation' => 'required|integer'],
            'email' => ['field' => 'email', 'validation' => 'required|email|max:255'],
            'password' => ['field' => 'password', 'validation' => 'required|string'],
            'edit_password' => ['field' => 'password', 'validation' => 'required|string|min:8|confirmed'],
            'new_password' => ['field' => 'password', 'validation' => 'required|string|min:8|confirmed'],
            'send_notifications' => ['field' => 'send_notifications', 'validation' => 'required|boolean'],
        ];
        
        if(!key_exists($type,$type_validations)){
            throw new Exception('Unknown Type: '. $type, 422);
        }

        $validation = $type_validations[$type]['validation'];
        $field = $type_validations[$type]['field'];

        if(!key_exists($field, $data)){
            throw new Exception("The $field field must be included.", 422);
        } else {
            $validator = Validator::make($data, [$field => $validation]);
            if($validator->fails()) {
                throw new Exception($validator->errors()->get($field)[0], 422);
            }
        }
        
        if($type == 'edit_password'){
            // Make sure passwords match up correctly
            if(!key_exists('current_password', $data)){
                throw new Exception('The current password field must be included.', 422);
            }

            if($data['current_password'] == ''){
                throw new Exception("Current password required", 422);
            }

            $current_password = $data['current_password'];
            $new_password = $data['password'];

            $password_results = User::where('id', $user_id)->select('password')->get()->first();
            if(!$password_results){
                throw new Exception('No user found.', 422);
            }

            if($current_password == $new_password){
                throw new Exception('New password must be different to current password.', 422);
            }

            if (!Hash::check($current_password, $password_results->password)) {
                throw new Exception('Incorrect current password.', 422);
            }

        }

    }

    private function get_token_data($token){
        // Get Apple Public Key
        $response = Http::get('https://appleid.apple.com/auth/keys')->json();
        // Decode Apple Login Token.
        return JWT::decode($token, JWK::parseKeySet($response), ['RS256']);
    }

    public function validate_apple_login($data){
        $token = $data['user_token'];
        $token_data = $this->get_token_data($token);

        if(stripos($token_data->aud, env('APP_BUNDLE_IDENTIFIER')) === false){
            Log::error('Invalid App Bundle Identifier. Potential breaking attempt.');
            return false;
        }

        if(strtolower($token_data->email) != strtolower($data['email'])){
            Log::error('Token email and given email not matching. Potential breaking attempt.');
            return false;
        }

        if(strtolower($token_data->sub) != strtolower($data['identifier'])){
            Log::error('Token user id and given user id not matching. Potential breaking attempt.');
            return false;
        }

        return true;
    }

    public function create_token($user, $notification_token = null){
        $token = $user->createToken($user->id)->plainTextToken;

        User::where('notification_token', $notification_token)->update(['notification_token' => NULL]);

        $update_fields = ['logged_in_at' => Carbon::now(), 'notification_token' => $notification_token];

        if(is_null($notification_token)){
            $update_fields['send_notifications'] = 0;
            $send_notifications = false;
        } else {
            $update_fields['send_notifications'] = 1;
            $send_notifications = true;
        }

        User::where('id', $user->id)->update($update_fields);
        
        return [
            'id' => $user->id, 
            'token' => $token, 
            'name' => $user->name, 
            'email' => $user->email,
            'supermarket_chain_id' => (int)$user->supermarket_chain_id,
            'region_id' => (int)$user->region_id,
            'send_notifications' => $send_notifications
        ];

    }
}
?>