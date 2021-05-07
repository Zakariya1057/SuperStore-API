<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\FeedbackService;
use App\Services\LoggerService;
use Illuminate\Http\Request;

class FeedbackController extends Controller {

    private $logger_service, $feedback_service;

    function __construct(LoggerService $logger_service, FeedbackService $feedback_service){
        $this->feedback_service = $feedback_service;
        $this->logger_service = $logger_service;
    }
    
    public function create(Request $request){
        
        $this->logger_service->log('feedback.create', $request);

        $validated_data = $request->validate([
            'data.message' => 'required',
        ]);

        $data = $validated_data['data'];

        $user = $request->user();
        $user_id = $user->id ?? null;

        $ip_address = $request->ip();

        $this->feedback_service->create($data['message'], $user_id, $ip_address);

        return response()->json(['data' => ['status' => 'success']]);
    }
}
