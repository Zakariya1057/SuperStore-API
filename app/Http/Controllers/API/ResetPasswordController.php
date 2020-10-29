<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\MailTrait;
use App\Traits\SanitizeTrait;
use App\Traits\UserTrait;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller {
    
    use MailTrait;
    use UserTrait;
    use SanitizeTrait;

    // Reset Password -> Send code
    // Validate -> Validate code
    // New Password -> Update Password & Send code

    public function send_code(Request $request){

        $validated_data = $request->validate([
            'data.email' => []
        ]);

        $validated_data = $this->sanitizeAllFields($validated_data);

        if(!key_exists('data',$validated_data)){
            throw new Exception('No email found.', 422);
        }

        $data = $validated_data['data'];

        $emailError = $this->validate_field($data,'email');

        if($emailError){
            return $emailError;
        }

        $user = User::where('email',$data['email'])->get()->first();

        if(is_null($user)){
            Log::error('No user found with email: '. $data['email']);
        } else {
            $code = mt_rand(1000000,9999999);
            User::where('id', $user->id)->update(['remember_token' => $code, 'token_sent_at' => Carbon::now()]);
            $this->mail_reset_password($user->email,$code,$user->name);
        }

        return response()->json(['data' => ['status' => 'success']]);

    }

    public function validate_code(Request $request){

        $validated_data = $request->validate([
            'data.email' => [],
            'data.code' => []
        ]);

        if(!key_exists('data',$validated_data)){
            throw new Exception('No code found.', 422);
        }

        $data = $this->sanitizeAllFields($validated_data['data']);

        $emailError = $this->validate_field($data,'email');
        $codeError = $this->validate_field($data,'code');

        if($emailError || $codeError){
            return $emailError ?? $codeError;
        }

        $user = User::where([ ['email',$data['email']], ['remember_token', $data['code']] ])->get()->first();
        if(is_null($user)){
            throw new Exception('Invalid code.', 422);
        }

        $token_time_diff = Carbon::createFromFormat('Y-m-d H:i:s', $user->token_sent_at)->diffInHours(NOW());
        if($token_time_diff >= 4){
            throw new Exception('Code expired please try sending another email.', 422);
        }

        return response()->json(['data' => ['status' => 'success']]);
    }

    public function new_password(Request $request){
        $validated_data = $request->validate([
            'data.email' => [],
            'data.code' => [],
            'data.password' => [],
            'data.password_confirmation' => []
        ]);

        $data = $this->sanitizeAllFields($validated_data['data']);

        if(!key_exists('data',$validated_data)){
            throw new Exception('No reset data found.', 422);
        }

        $emailError = $this->validate_field($data,'email');
        $passwordError = $this->validate_field($data,'new_password');
        $codeError = $this->validate_field($data,'code');

        if($emailError || $codeError || $passwordError){
            return $emailError ?? $codeError ?? $passwordError;
        }

        $user = User::where('email', $data['email'])->get()->first();

        if(!$user){
            // This really shouldnt happen
            return response()->json(['data' => ['status' => 'success']]);
        }

        $token_time_diff = Carbon::createFromFormat('Y-m-d H:i:s', $user->token_sent_at)->diffInHours(NOW());

        if($token_time_diff){
            throw new Exception('Code expired please try again.', 422);
        }
        
        User::where([ ['email', $data['email']],['remember_token', $data['code']] ])->update([
            'remember_token' => null,
            'password' => Hash::make($data['password']),
        ]);

        
        $user->tokens()->delete();
        $token = $user->createToken($user->id)->plainTextToken;

        User::where('id', $user->id)->update(['logged_in_at' => Carbon::now()]);
        return response()->json(['data' => ['id' => $user->id, 'token' => $token, 'name' => $user->name, 'email' => $user->email]]);

    }

}
