<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;

trait NotificationTrait {

    protected function monitored_price_change(){
        // Send notification to user phone.

        // Need user device token, product item.
        
        $deviceToken = "219c867c8a72e4d5264d44abd8d4169514523cece0ce2f20beb166379be1e238";
        $filePath = "/home/zack/superstore_api/storage/apple_push_notification_production.pem";
        $passphrase = "GFDAxqVADmNa22Q6jJRVy9gtXSJYpfSPHcU9r8dL64yCkP5kkW";

        $message = ["title" => "Price Change", "body" => "£20.40 - ASDA Grower's Selection Watermelon"];

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $filePath);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        
        $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', 
            $err, 
            $errstr, 
            60, 
            STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, 
            $ctx);
        
        // Create the payload body
        $body['aps'] = array(
            'badge' => +1,
            'alert' => $message,
            'sound' => 'default',
            'data' => [
                'product_id' => 100,
                'name' => 'Selection Watermelon'
            ]
        );
        
        $payload = json_encode($body);
        
        // Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
        
        // Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));
        
        if (!$result)
            echo 'Message not delivered' . PHP_EOL;
        else
            echo 'Message successfully delivered: '.$message["title"]. PHP_EOL;
        
        // Close the connection to the server
        fclose($fp);

    }

}