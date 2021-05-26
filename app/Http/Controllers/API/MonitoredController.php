<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Logger\LoggerService;
use App\Services\Product\MonitoringService;
use App\Services\Sanitize\SanitizeService;

class MonitoredController extends Controller {
    
    private $sanitize_service, $monitoring_service;

    function __construct(SanitizeService $sanitize_service, MonitoringService $monitoring_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->monitoring_service = $monitoring_service;
        $this->logger_service = $logger_service;
    }

    public function index(Request $request){
        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.store_type_id' => 'required',
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $store_type_id = $data['store_type_id'];

        $this->logger_service->log('monitor.index', $request);

        $products = $this->monitoring_service->all($user_id, $store_type_id);
        return response()->json(['data' => $products ]);
    }

    public function update($product_id, Request $request){
        $user_id = $request->user()->id;

        $this->logger_service->log('monitor.update', $request);

        $validated_data = $request->validate([
            'data.monitor' => 'required',
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $monitor = $data['monitor'];

        $this->monitoring_service->update($user_id, $product_id, $monitor);

        return response()->json(['data' => ['status' => 'success']]);

    }
    
}
