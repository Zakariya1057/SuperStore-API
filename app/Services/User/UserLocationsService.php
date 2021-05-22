<?php

namespace App\Services\User;

use App\Models\UserLocation;

class UserLocationsService {
    public function create($latitude, $longitude, $ip_address, $user_id = null){
        UserLocation::insertOrIgnore([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'ip_address' => $ip_address,
            'user_id' => $user_id
        ]);
    }
}
?>