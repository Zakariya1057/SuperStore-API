<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MailService;
use App\Services\SanitizeService;
use App\Services\UserService;
use App\Traits\UserTrait;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller {
    
    private $sanitize_service, $mail_service, $user_service;

    function __construct(SanitizeService $sanitize_service, MailService $mail_service, UserService $user_service){
        $this->sanitize_service = $sanitize_service;
        $this->mail_service = $mail_service;
        $this->user_service = $user_service;
    }

    // Reset Password -> Send code
    // Validate -> Validate code
    // New Password -> Update Password & Send code

    public function send_code(Request $request){

        $validated_data = $request->validate([
            'data.email' => 'required|email|max:255',
        ]);

        $validated_data = $this->sanitize_service->sanitizeAllFields($validated_data);

        $data = $validated_data['data'];

        $user = User::where('email',$data['email'])->get()->first();

        if(is_null($user)){
            Log::error('No user found with email: '. $data['email']);
        } else {
            $code = mt_rand(1000000,9999999);
            User::where('id', $user->id)->update(['remember_token' => $code, 'token_sent_at' => Carbon::now()]);
            $this->mail_service->send_reset_email($user->email,$code,$user->name);
        }

        return response()->json(['data' => ['status' => 'success']]);

    }

    public function validate_code(Request $request){

        $validated_data = $request->validate([
            'data.email' => 'required|email|max:255',
            'data.code' => 'required|integer'
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $user = User::where([['email',$data['email']], ['remember_token', $data['code']] ])->get()->first();
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
            'data.code' => 'required|integer',
            'data.email' => 'required|email|max:255',
            'data.password' => 'required|confirmed|string|min:8|max:255',
            'data.notification_token' => ''
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $notification_token = $data['notification_token'];

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

        $token_data = $this->user_service->create_token($user, $notification_token);
        return response()->json(['data' => $token_data]);

    }

}
