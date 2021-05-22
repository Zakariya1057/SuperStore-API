<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Logger\LoggerService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller {

    private $logger_service, $report_service;

    function __construct(LoggerService $logger_service, ReportService $report_service){
        $this->report_service = $report_service;
        $this->logger_service = $logger_service;
    }
    
    public function create(Request $request){
        
        $this->logger_service->log('feedback.create', $request);

        $validated_data = $request->validate([
            'data.issue' => 'required',
        ]);

        $data = $validated_data['data'];

        $user = $request->user();
        $user_id = $user->id ?? null;

        $ip_address = $request->ip();

        $this->report_service->create($data['issue'],$user_id, $ip_address);

        return response()->json(['data' => ['status' => 'success']]);
    }
}