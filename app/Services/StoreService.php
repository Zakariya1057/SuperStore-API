<?php

namespace App\Services;

use App\Models\OpeningHour;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Models\StoreType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreService {

    public function stores_by_type($store_type_id, $opening_hours=true, $latitude = null, $longitude=null){

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
        ->join('store_locations','store_locations.store_id', '=','stores.id')
        ->where([ ['stores.enabled', 1]])
        ->whereNotNull('longitude')
        ->limit(20);

        $location_fields = [
            'city',
            'address_line1',
            'address_line2',
            'address_line3',
            'postcode',
            'latitude',
            'longitude',
        ];

        $hours_fields = ['opens_at','closes_at','day_of_week','closed_today'];

        if($opening_hours){
            $day_of_week = Carbon::now()->dayOfWeek == 0 ? 6 : Carbon::now()->dayOfWeek - 1;
            $select = array_merge($select, $hours_fields);

            $query_builder = $query_builder->join('opening_hours', function ($join) use($day_of_week) {
                $join->on('opening_hours.store_id', '=', 'stores.id')->where('day_of_week', '=', $day_of_week);
            });
        } 

        if(!is_null($latitude) && !is_null($longitude)){
            $select[] = DB::raw('( 6367 * acos( cos( radians('.$latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$longitude.') ) + sin( radians('.$latitude.') ) * sin( radians( latitude ) ) ) ) AS distance');

            $query_builder = $query_builder
            // ->having('distance', '<', 50)
            // ->having('distance', '>', 0)
            ->orderBy('distance');
        }

        $stores = $query_builder->select($select)->withCasts($casts)->get();

        foreach($stores as $store){
            $location = [];
            
            if($opening_hours){
                $hour = new OpeningHour();

                foreach($hours_fields as $field){
                    $hour->{$field} = $store->{$field};
                    unset($store->{$field});
                }
    
                $store->opening_hours = [$hour];
            }

            foreach($location_fields as $field){
                $location[$field] = $store->{$field};
                unset($store->{$field});
            }

            $store->location = $location;
        }

        return $stores ?? [];

    }

}
?>