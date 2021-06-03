<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonitorUpdateRequest;
use App\Http\Requests\MonitorViewRequest;
use App\Services\Logger\LoggerService;
use App\Services\Product\MonitoringService;
use App\Services\Sanitize\SanitizeService;
use Illuminate\Support\Facades\Auth;

class MonitoredController extends Controller {
    
    private $sanitize_service, $monitoring_service;

    function __construct(SanitizeService $sanitize_service, MonitoringService $monitoring_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->monitoring_service = $monitoring_service;
        $this->logger_service = $logger_service;
    }

    public function index(MonitorViewRequest $request){
        $user_id = Auth::id();

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $region_id = $data['region_id'] ?? 8;
        $store_type_id = $data['store_type_id'];

        $this->logger_service->log('monitor.index', $request);

        $products = $this->monitoring_service->all($user_id, $region_id, $store_type_id);
        return response()->json(['data' => $products ]);
    }

    public function update($product_id, MonitorUpdateRequest $request){
        $user_id = Auth::id();

        $this->logger_service->log('monitor.update', $request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $monitor = $data['monitor'];

        $this->monitoring_service->update($user_id, $product_id, $monitor);

        return response()->json(['data' => ['status' => 'success']]);

    }
    
}
