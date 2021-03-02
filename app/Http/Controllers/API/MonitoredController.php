<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MonitoredProduct;
use App\Services\LoggerService;
use App\Services\MonitoringService;
use App\Services\SanitizeService;

class MonitoredController extends Controller {
    
    private $sanitize_service, $monitoring_serve;

    function __construct(SanitizeService $sanitize_service, MonitoringService $monitoring_serve, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->monitoring_serve = $monitoring_serve;
        $this->logger_service = $logger_service;
    }

    public function index(Request $request){
        $user_id = $request->user()->id;

        $this->logger_service->log('monitor.index', $request);

        $products = $this->monitoring_serve->monitoring_products($user_id);
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

        if ($monitor) {
            if( !MonitoredProduct::where([ ['user_id', $user_id], ['product_id', $product_id] ])->exists()) {
                $favourite = new MonitoredProduct();
                $favourite->product_id = $product_id;
                $favourite->user_id = $user_id;
                $favourite->save();
            }
        } else {
            MonitoredProduct::where([ ['user_id', $user_id], ['product_id', $product_id] ])->delete();
        }

        return response()->json(['data' => ['status' => 'success']]);

    }
    
}
