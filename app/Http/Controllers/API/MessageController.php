<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Services\Logger\LoggerService;
use App\Services\Message\MessageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    
    private $logger_service, $message_service;

    function __construct(LoggerService $logger_service, MessageService $message_service){
        $this->message_service = $message_service;
        $this->logger_service = $logger_service;
    }

    public function index(Request $request){
        $this->logger_service->log('message.index', $request);

        $user_id = Auth::id();

        $message_type = $request->input('type');

        $messages = $this->message_service->index($message_type, $user_id);

        return response()->json(['data' => $messages]);
    }

    public function create(MessageRequest $request){
        $this->logger_service->log('message.create', $request);

        $validated_data = $request->validated();
        $data = $validated_data['data'];

        $message = $data['message'];
        $type = $data['type'];

        $user_id = Auth::id();

        $message = $this->message_service->create($message, $type, $user_id);
        
        return response()->json(['data' => $message]);
    }
}
