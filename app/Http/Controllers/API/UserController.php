<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\LoggerService;
use App\Services\SanitizeService;
use App\Services\UserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller {

    private $sanitize_service, $user_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, UserService $user_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->user_service = $user_service;
        $this->logger_service = $logger_service;
    }

    public function register(Request $request){

        $this->logger_service->log('user.register', $request);

        $validated_data = $request->validate([
            'data.name' => 'required|string|max:255',
            'data.email' => 'required|email|max:255',
            'data.password' => 'required|confirmed|string|min:8|max:255',
            'data.identifier' => '',
            'data.user_token' => '',
            'data.notification_token' => ''
        ]);
        
        $notification_token = $data['notification_token'] ?? null;

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);
        $user = $this->user_service->register($data);

        $token_data = $this->user_service->create_token($user, $notification_token);
        return response()->json(['data' => $token_data]);

    }
    

    public function login(Request $request){

        $this->logger_service->log('user.login', $request);

        $validated_data = $request->validate([
            'data.email' => 'required|email|max:255',
            'data.password' => 'required|string|min:8|max:255',
            'data.notification_token' => ''
        ]);
        
        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);
        
        $notification_token = $data['notification_token'];

        $user = $this->user_service->login($data);
       
        $user->tokens()->delete();
        $token_data = $this->user_service->create_token($user, $notification_token);
        return response()->json(['data' => $token_data]);

    }

    public function logout(Request $request){
        $this->logger_service->log('user.logout', $request);
        $request->user()->tokens()->delete();
        User::where('id', $request->user()->id)->update(['logged_out_at' => Carbon::now(), 'notification_token' => NULL]);
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function update(Request $request){

        $this->logger_service->log('user.update', $request);

        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.email' => [],
            'data.name' => [],
            'data.password' => [],
            'data.password_confirmation' => [],
            'data.current_password' => [],
            'data.type' => [],
            'data.send_notifications' => [],
            'data.notification_token' =>  []
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        if(!key_exists('type',$data)){
            throw new Exception('Field Type required.', 422);
        }
        
        $this->user_service->update($data, $user_id);

        return response()->json(['data' => ['status' => 'success']]);

    }

    public function delete(Request $request){
        $user = $request->user();

        $this->logger_service->log('user.delete', $request);

        $this->user_service->delete($user);

        $request->user()->tokens()->delete();

        return response()->json(['data' => ['status' => 'success']]);
    }

}
