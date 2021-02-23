<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoggerService {
    public function log($route, Request $request){
        
        $route = strtoupper($route);

        $user = $request->user();
        $logged_in = !is_null($user);
        preg_match('/api(.+)/', $request->url(), $matches);
        $url = urldecode($matches[1]);

        $ip_address = $request->ip();
        $body =json_decode(request()->getContent(), true) ?? ['Query' => $request->segment(count(request()->segments()))];

        $log_data = $logged_in ? [ ['id' => $user->id, 'name' => $user->name, 'email' => $user->email], ['url' => $url, 'body' => $body] ] : ['url' => $url, 'body' => $body];

        Log::channel('request')->info("$ip_address | $route REQUEST", $log_data);
    }
}

?>