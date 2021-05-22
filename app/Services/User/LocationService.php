<?php

namespace App\Services\User;

use App\Models\User;

class LocationService {
    public function update_location($user_id, $latitude, $longitude){
        User::where('id', $user_id)->update([
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);
    }
}

?>