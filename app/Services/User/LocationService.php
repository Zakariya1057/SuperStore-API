<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\UserLocation;

class LocationService {
    public function update_location($user_id, $latitude, $longitude){
        User::where('id', $user_id)->update([
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);
    }

    public function record_location(float $latitude, float $longitude, $ip_address, ?int $user_id, ?int $region_id = null, ?int $supermarket_chain_id = null){

        if(!is_null($user_id)){
            $this->update_location($user_id, $latitude, $longitude);
        }

        $user_location = new UserLocation();

        $user_location->ip_address = $ip_address;

        $user_location->latitude = $latitude;
        $user_location->longitude = $longitude;

        $user_location->user_id = $user_id;

        $user_location->region_id = $region_id;
        $user_location->supermarket_chain_id = $supermarket_chain_id;

        $user_location->save();
    }
}

?>