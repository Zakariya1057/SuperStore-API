<?php

namespace App\Traits;

use App\OpeningHour;
use App\Store;
use App\StoreLocation;
use App\StoreType;
use Carbon\Carbon;

trait StoreTrait {

    public function stores_by_type($store_type_id,$opening_hours=true){

        $hour = new OpeningHour();
        $store_type = new StoreType();
        $location = new StoreLocation();

        $casts = array_merge($hour->casts, $store_type->casts,$location->casts);

        $select = [
            'stores.*', 
            'store_types.large_logo', 
            'store_types.small_logo',

            'store_locations.city',
            'store_locations.postcode',
            'store_locations.address_line1',
            'store_locations.address_line2',
            'store_locations.address_line3',
            'store_locations.longitude',
            'store_locations.latitude',
        ];

        $query_builder = Store::where('store_type_id', $store_type_id)
        ->join('store_types', 'store_types.id', '=', 'stores.store_type_id')
        ->join('store_locations','store_locations.store_id', '=','stores.id');

        if($opening_hours){
            $day_of_week = Carbon::now()->dayOfWeek == 0 ? 6 : Carbon::now()->dayOfWeek - 1;

            array_push($select, 'closes_at','opens_at');
            $query_builder = $query_builder->join('opening_hours', function ($join) use($day_of_week) {
                $join->on('opening_hours.store_id', '=', 'stores.id')->where('day_of_week', '=', $day_of_week);
            });
        } 

        $location_fields = [
            'city',
            'address_line1',
            'address_line2',
            'address_line3',
            'postcode',
            'latitude',
            'longitude',
        ];

        $stores = $query_builder->select($select)->withCasts($casts)->get();

        foreach($stores as $store){
            $location = [];
            
            foreach($location_fields as $field){
                $location[$field] = $store->{$field};
                unset($store->{$field});
            }

            $store->location = $location;
        }

        return $stores;

    }

}

?>