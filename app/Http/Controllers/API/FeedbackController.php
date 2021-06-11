<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedbackRequest;
use App\Services\Logger\LoggerService;
use App\Services\Feedback\FeedbackService;

class FeedbackController extends Controller {

    private $logger_service, $feedback_service;

    function __construct(LoggerService $logger_service, FeedbackService $feedback_service){
        $this->feedback_service = $feedback_service;
        $this->logger_service = $logger_service;
    }
    
    public function create(FeedbackRequest $request){
        
        $this->logger_service->log('feedback.create', $request);

        $validated_data = $request->validated();

        $data = $validated_data['data'];

        $message = $data['message'];
        $type = $data['type'];

        $user = $request->user();
        $user_id = $user->id ?? null;
        $ip_address = $request->ip();

        $this->feedback_service->create($message, $type, $user_id, $ip_address);

        return response()->json(['data' => ['status' => 'success']]);
    }
}
