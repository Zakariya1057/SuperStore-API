<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserResetService extends UserAuthService {

    private $mail_service;

    function __construct(MailService $mail_service){
        $this->mail_service = $mail_service;
    }

    public function send_code($data){
        $user = User::where('email',$data['email'])->get()->first();

        if(is_null($user)){
            Log::error('No user found with email: '. $data['email']);
            // throw new Exception('No user found with email: '. $data['email'])
        } else {
            $code = mt_rand(1000000,9999999);
            User::where('id', $user->id)->update(['remember_token' => $code, 'token_sent_at' => Carbon::now()]);
            $this->mail_service->send_reset_email($user->email,$code,$user->name);
        }
    }

    public function validate_code($data){

        $email = $data['email'];
        $token = $data['code'];

        $user = User::where('email',$email)->get()->first();

        if(!$this->token_valid($user, $token)){
            throw new Exception('Invalid code.', 422);
        }

        if($this->token_expired($user->token_sent_at)){
            throw new Exception('Code expired please try sending another email.', 422);
        }
    }

    public function update_password($data): ?User {
        $token_code = $data['code'];
        $user_email = $data['email'];

        $user = User::where([ ['email', $user_email], 'remember_token' => $token_code ])->get()->first();

        // If no user or token not in use anymore.
        if(!$user){
            throw new Exception('Error occured. Please try again from the start.', 422);
        }

        if($this->token_expired($user->token_sent_at)){
            throw new Exception('Code expired please try again from the start.', 422);
        }
        
        User::where([ ['email', $data['email']],['remember_token', $data['code']] ])->update([
            'remember_token' => null,
            'token_sent_at' => null,
            'password' => Hash::make($data['password']),
        ]);

        return $user;

    }

    private function token_valid($user, $code): bool {
        return $user->remember_token == $code;
    }

    private function token_expired($token_sent_at): bool {
        $token_time_diff = Carbon::createFromFormat('Y-m-d H:i:s', $token_sent_at)->diffInHours(NOW());
        return $token_time_diff >= 24;
    }

}
?>