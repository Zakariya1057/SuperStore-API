<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService {
    
    public function send_reset_email($email_to,$reset_code,$name_to){

        Log::debug("Sending Reset Password To $name_to($email_to)");
        $logo = env("APP_URL") . '/api/image/company/logo_small.png';

        Mail::send('email.reset-password', ['logo' => $logo, 'name' => $name_to,'code' => $reset_code], function ($message) use($email_to) {
            $email_from_address = env('MAIL_FROM_ADDRESS');
            $email_from_name = env('MAIL_FROM_ADDRESS');
            $reset_password_subject = env('RESET_PASSWORD_SUBJECT');

            $message->from($email_from_address, $email_from_name);
            $message->to($email_to)->subject($reset_password_subject);
        });

    }

}
?>