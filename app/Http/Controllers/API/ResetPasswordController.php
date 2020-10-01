<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\MailTrait;
use App\Traits\UserTrait;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    use MailTrait;
    use UserTrait;

    // Reset Password -> Send token
    // Validate -> Validate token
    // New Password -> Update Password & Send token

    public function send_token(Request $request){

        $validated_data = $request->validate([
            'data.email' => []
        ]);

        if(!key_exists('data',$validated_data)){
            return response()->json(['data' => ['error' => 'No email found.']], 422);
        }

        $data = $validated_data['data'];

        $emailError = $this->validate_field($data,'email');

        if($emailError){
            return $emailError;
        }

        $user = User::where('email',$data['email'])->get()->first();

        if(is_null($user)){
            return response()->json(['data' => ['error' => 'no user found with email.']], 404);
        }

        $token = mt_rand(1000000,9999999);

        User::where('id', $user->id)->update(['remember_token' => $token, 'token_sent_at' => Carbon::now()]);

        $this->mail_reset_password($user->email,$token,$user->name);

        return response()->json(['data' => ['status' => 'success']]);
    }

    public function validate_token(Request $request){

        $validated_data = $request->validate([
            'data.email' => [],
            'data.token' => []
        ]);

        if(!key_exists('data',$validated_data)){
            return response()->json(['data' => ['error' => 'No token data found.']], 422);
        }

        $data = $validated_data['data'];

        $emailError = $this->validate_field($data,'email');
        $tokenError = $this->validate_field($data,'token');

        if($emailError || $tokenError){
            return $emailError ?? $tokenError;
        }

        if(!User::where([ ['email',$data['email']],['remember_token', $data['token']] ])->exists()){
            return response()->json(['data' => ['error' => 'Invalid token.']], 422);
        }

        return response()->json(['data' => ['status' => 'success']]);
    }

    public function new_password(Request $request){
        $validated_data = $request->validate([
            'data.email' => [],
            'data.token' => [],
            'data.password' => [],
            'data.password_confirmation' => []
        ]);

        if(!key_exists('data',$validated_data)){
            return response()->json(['data' => ['error' => 'No reset data found.']], 422);
        }

        $data = $validated_data['data'];

        $emailError = $this->validate_field($data,'email');
        $passwordError = $this->validate_field($data,'new_password');
        $tokenError = $this->validate_field($data,'token');

        if($emailError || $tokenError || $passwordError){
            return $emailError ?? $tokenError ?? $passwordError;
        }

        
        User::where([ ['email', $data['email']],['remember_token', $data['token']] ])->update([
            'remember_token' => null,
            'password' => Hash::make($data['password']),
        ]);

        $user = User::where('email', $data['email'])->get()->first();

        $user->tokens()->delete();
        $token = $user->createToken($user->id)->plainTextToken;

        User::where('id', $user->id)->update(['logged_in_at' => Carbon::now()]);
        return response()->json(['data' => ['token' => $token, 'name' => $user->name, 'email' => $user->email]]);

    }

}
