<?php

namespace App\Services;

use App\Models\ReportIssue;

class ReportService {

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service)
    {
        $this->sanitize_service = $sanitize_service;
    }
    
    public function create($issue, $user_id, $ip_address){
        $feedback = new ReportIssue();
        
        $feedback->issue = $this->sanitize_service->sanitizeField($issue);
        $feedback->ip_address = $ip_address;
        $feedback->user_id = $user_id;

        $feedback->save();
    }
}
?>