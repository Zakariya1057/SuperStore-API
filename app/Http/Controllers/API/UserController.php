<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserLocationRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Services\User\LocationService;
use App\Services\Logger\LoggerService;
use App\Services\Sanitize\SanitizeService;
use App\Services\User\UserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller {

    private $sanitize_service, $user_service, $logger_service, $location_service;

    function __construct(SanitizeService $sanitize_service, UserService $user_service, LoggerService $logger_service, LocationService $location_service){
        $this->sanitize_service = $sanitize_service;
        $this->user_service = $user_service;
        $this->logger_service = $logger_service;
        $this->location_service = $location_service;
    }

    public function register(UserRegisterRequest $request){

        $this->logger_service->log('user.register', $request);

        $validated_data = $request->validated();
        
        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);
        
        $user = $this->user_service->register($data);

        $notification_token = $data['notification_token'] ?? null;

        $token_data = $this->user_service->create_token($user, $notification_token);
        return response()->json(['data' => $token_data]);

    }
    

    public function login(UserLoginRequest $request){

        $this->logger_service->log('user.login', $request);

        $validated_data = $request->validated();
        
        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);
        
        $notification_token = $data['notification_token'];

        $user = $this->user_service->login($data);
       
        $user->tokens()->delete();
        $token_data = $this->user_service->create_token($user, $notification_token);
        return response()->json(['data' => $token_data]);

    }

    public function logout(Request $request){
        $this->logger_service->log('user.logout', $request);

        Auth::logout();

        $request->session()->invalidate();
    
        $request->session()->regenerateToken();

        User::where('id', $request->user()->id)->update(['logged_out_at' => Carbon::now(), 'notification_token' => NULL]);
        
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function update(UserUpdateRequest $request){

        $this->logger_service->log('user.update', $request);

        $user_id = Auth::id();

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        if(!key_exists('type',$data)){
            throw new Exception('Field Type required.', 422);
        }

        // Remove later
        if($data['type'] != 'store_type_id'){
            $this->user_service->update($data, $user_id);
        }

        return response()->json(['data' => ['status' => 'success']]);

    }

    public function location(UserLocationRequest $request){

        $this->logger_service->log('user.location', $request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $latitude = $data['latitude'];
        $longitude = $data['longitude'];

        $user_id = Auth::id();

        $this->location_service->update_location($user_id, $latitude, $longitude);

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
