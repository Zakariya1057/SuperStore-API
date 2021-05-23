<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Logger\LoggerService;
use App\Services\Store\FlyerService;
use Illuminate\Http\Request;

class FlyerController extends Controller {
    private $logger_service, $flyer_service;

    function __construct(LoggerService $logger_service, FlyerService $flyer_service){
        $this->flyer_service = $flyer_service;
        $this->logger_service = $logger_service;
    }
    
    public function index($store_id, Request $request){
        $this->logger_service->log('flyer.index', $request);
        return response()->json(['data' => $this->flyer_service->all($store_id) ]);
    }

    public function show($name, Request $request){
        $this->logger_service->log('flyer.show', $request);
        $flyer = $this->flyer_service->show($name);
        return response($flyer, 200)->header('Content-Type', 'application/pdf');

    }
}
