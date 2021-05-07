<?php

namespace App\Services;

use App\Models\FeedbackMessage;

class FeedbackService {

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service)
    {
        $this->sanitize_service = $sanitize_service;
    }
    
    public function create($message, $user_id, $ip_address){
        $feedback = new FeedbackMessage();
        
        $feedback->message = $this->sanitize_service->sanitizeField($message);
        $feedback->ip_address = $ip_address;
        $feedback->user_id = $user_id;

        $feedback->save();
    }
}
?>