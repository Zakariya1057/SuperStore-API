<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordNewPasswordRequest;
use App\Http\Requests\ResetPasswordSendCodeRequest;
use App\Http\Requests\ResetPasswordValidateCodeRequest;
use App\Services\Logger\LoggerService;
use App\Services\Sanitize\SanitizeService;
use App\Services\User\UserResetService;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller {
    
    private $sanitize_service, $user_reset_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, UserResetService $user_reset_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->user_reset_service = $user_reset_service;
        $this->logger_service = $logger_service;
    }

    // 1. Reset Password -> Send code
    // 2. Validate -> Validate code
    // 3. New Password -> Update Password & Send code

    public function send_code(ResetPasswordSendCodeRequest $request){

        $this->logger_service->log('reset password.send_code', $request);

        $validated_data = $request->validated();

        $validated_data = $this->sanitize_service->sanitizeAllFields($validated_data);

        $data = $validated_data['data'];

        $this->user_reset_service->send_code($data);

        return response()->json(['data' => ['status' => 'success']]);

    }

    public function validate_code(ResetPasswordValidateCodeRequest $request){

        $this->logger_service->log('reset password.validate_code', $request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $this->user_reset_service->validate_code($data);

        return response()->json(['data' => ['status' => 'success']]);
    }

    public function new_password(ResetPasswordNewPasswordRequest $request){

        $this->logger_service->log('reset password.new_password', $request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $notification_token = $data['notification_token'];
        $user = $this->user_reset_service->update_password($data);
        
        $user->tokens()->delete();
        $token_data = $this->user_reset_service->create_token($user, $notification_token);

        return response()->json(['data' => $token_data]);

    }

}
