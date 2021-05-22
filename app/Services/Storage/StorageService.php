<?php

namespace App\Services\Storage;

use Aws\S3\S3Client;

class StorageService {
    public function get($path, $bucket = 'images'){
        $aws_config = (object)config('aws');

        $s3 = new S3Client([
            'version' => $aws_config->version,
            'region'  => $aws_config->region,
            'credentials' => $aws_config->credentials
        ]);	
            
        $result = $s3->getObject([
            'Bucket' => 'superstore.' . $bucket,
            'Key'    => $path
        ]);

        return $result['Body'];
    }
}
?>