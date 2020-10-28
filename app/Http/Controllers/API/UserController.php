<?php

namespace App\Http\Controllers\API;

use App\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Product;
use App\Review;
use App\Traits\SanitizeTrait;
use App\Traits\UserTrait;
use Carbon\Carbon;
use Firebase\JWT\JWK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use \Firebase\JWT\JWT;

class UserController extends Controller {

    use UserTrait;
    use SanitizeTrait;

    public function register(Request $request){

        $validated_data = $request->validate([
            'data.name' => [],
            'data.email' => [],
            'data.password' => [],
            'data.password_confirmation' => [],
            'data.identifier' => [],
            'data.user_token' => []
        ]);

        $data = $this->sanitizeAllFields($validated_data['data']);
        
        $nameError = $this->validate_field($data,'name');
        $emailError = $this->validate_field($data,'email');
        $passwordError = $this->validate_field($data,'new_password');

        if($nameError || $passwordError || $emailError){
            return $nameError ?? $passwordError ?? $emailError;
        }

        $identifier = $data['identifier'] ?? '';
        $user_token = $data['user_token'] ?? '';
        
        $user_data = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ];

        $user = User::where('email', $data['email'])->get()->first();
        $userExists = !is_null($user);

        if( $identifier != "" && $user_token != ""){
            // Apple Login

            if(!$this->validate_apple_login($data)){
                return response()->json(['data' => ['error' => 'Invalid user data provided.']], 422);
            } else {

                $apple_user = User::where('identifier', $identifier)->get()->first();

                // User Found In Database. Or user exists with that email.
                if( !is_null($apple_user) || $userExists){
                    $token_data = $this->create_token($apple_user ?? $user);
                    return response()->json(['data' => $token_data]);
                } else {
                    // Either:
                    // New User or
                    // User created with email then now trying to log in with apple.  Account belongs to user without apple login. Login Anyway

                    $user_data['identifier'] = $identifier;

                }

            }

        }

        if( $userExists ){
            return response()->json(['data' => ['error' => 'Email address belongs to another user.']], 422);
        }

        $user = User::create($user_data);

        $token = $user->createToken($user->id)->plainTextToken;

        User::where('id', $user->id)->update(['logged_in_at' => Carbon::now()]);

        return response()->json(['data' => ['id' => $user->id,'token' => $token, 'name' => $user->name, 'email' => $user->email]]);
    }
    

    public function login(Request $request){

        $validated_data = $request->validate([
            'data.email' => [],
            'data.password' => []
        ]);
        
        $data = $this->sanitizeAllFields($validated_data['data']);

        $emailError = $this->validate_field($data,'email');
        $passwordError = $this->validate_field($data,'password');

        if($emailError || $passwordError){
            return $emailError ?? $passwordError;
        }

        $user = User::where('email', $data['email'])->get()->first();

        if(!$user){
            return response()->json(['data' => ['error' => 'Email address doesn\'t belongs to any user.']], 404);
        }

        if (Hash::check($data['password'], $user->password)) {
            $user->tokens()->delete();
            $token = $user->createToken($user->id)->plainTextToken;
            User::where('id', $user->id)->update(['logged_in_at' => Carbon::now()]);
            return response()->json(['data' => ['id' => $user->id, 'token' => $token, 'name' => $user->name, 'email' => $user->email]]);
        } else {

            if(!is_null($user->identifier)){
                return response()->json(['data' => ['error' => 'Your account is connected to Apple. Use the Apple button to log in.']], 422);
            } else {
                return response()->json(['data' => ['error' => 'Wrong password.']], 404);
            }
            
        }

    }

    public function logout(Request $request){
        $request->user()->tokens()->delete();
        User::where('id', $request->user()->id)->update(['logged_out_at' => Carbon::now()]);
        return response()->json(['data' => ['status' => 'sucess']]);
    }

    public function update(Request $request){

        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.email' => [],
            'data.name' => [],
            'data.password' => [],
            'data.password_confirmation' => [],
            'data.current_password' => [],
            'data.type' => [],
        ]);

        $data = $this->sanitizeAllFields($validated_data['data']);

        if(!key_exists('type',$data)){
            return response()->json(['data' => ['error' => 'Type required.']], 422);
        }

        $type = $data['type'];
        $value = $data[ $data['type'] ];

        if($type == 'password'){
            $type = 'edit_password';
        }

        $error = $this->validate_field($data,$type,$request->user()->id);
        if($error){
            return $error;
        }

        if($type == 'edit_password'){
           $value  = Hash::make($value);
        }

        User::where('id',$user_id)->update([$data['type'] => $value ]);

        return response()->json(['data' => ['status' => 'sucess']]);

    }

    public function delete(Request $request){
        $user = $request->user();

        $reviews = Review::where('user_id', $user->id)->join('products', 'products.id', 'reviews.product_id')->groupBy('products.store_type_id')->get();

        foreach($reviews as $review){
            if($user->id == $review->store_type_id){
                return response()->json(['data' => ['error' => 'Failed to delete store account']], 402);
            }

            Review::where('user_id', $user->id)->update(['user_id' => $review->store_type_id]);
        }

        User::where('id', $user->id)->delete();

        $request->user()->tokens()->delete();

        return response()->json(['data' => ['status' => 'sucess']]);
    }

    private function create_token($user){
        $token = $user->createToken($user->id)->plainTextToken;
        return ['id' => $user->id, 'token' => $token, 'name' => $user->name, 'email' => $user->email];
    }

}
