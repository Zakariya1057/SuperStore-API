<?php

namespace App\Services\Feedback;

use App\Models\FeedbackMessage;
use App\Services\Sanitize\SanitizeService;

class FeedbackService {

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service)
    {
        $this->sanitize_service = $sanitize_service;
    }
    
    public function create(string $message, string $type, ?int $user_id, $ip_address){
        $feedback = new FeedbackMessage();
        
        $feedback->message = $this->sanitize_service->sanitizeField($message);
        $feedback->type = $this->sanitize_service->sanitizeField($type);
        
        $feedback->ip_address = $ip_address;
        $feedback->user_id = $user_id;

        $feedback->save();
    }
}
?>