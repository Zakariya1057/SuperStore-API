<?php

namespace App\Services\Store;

use App\Models\OpeningHour;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Models\SupermarketChain;
use App\Services\User\LocationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreService {

    private $location_service;

    public function __construct(LocationService $location_service){
        $this->location_service = $location_service;
    }

    public function stores_by_supermarket_chains(int $supermarket_chain_id, $opening_hours=true, $latitude = null, $longitude=null){

        $hour = new OpeningHour();
        $supermarket_chain = new SupermarketChain();
        $location = new StoreLocation();

        if(!is_null($latitude) && !is_null($longitude)){
            $this->location_service->record_location($latitude, $longitude, request()->ip(), Auth::id(), null, $supermarket_chain_id);
        }

        $casts = array_merge($hour->casts, $supermarket_chain->casts, $location->casts);

        $select = [
            'stores.*',
            
            'store_locations.region_id',

            'store_locations.city',
            'store_locations.postcode',
            'store_locations.address_line1',
            'store_locations.address_line2',
            'store_locations.address_line3',
            'store_locations.longitude',
            'store_locations.latitude',
        ];

        $query_builder = Store::where('supermarket_chain_id', $supermarket_chain_id)
        ->join('supermarket_chains', 'supermarket_chains.id', '=', 'stores.supermarket_chain_id')
        ->join('store_locations','store_locations.store_id', '=','stores.id')
        ->where([ ['stores.enabled', 1]])
        ->whereNotNull('longitude')
        ->limit(20);

        $location_fields = [
            'region_id',

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
            $select[] = DB::raw('( 6367 * acos( cos( radians('.$latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians('.$latitude.') ) * sin( radians( latitude ) ) ) ) AS distance');

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