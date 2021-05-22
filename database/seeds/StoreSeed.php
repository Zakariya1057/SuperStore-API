<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('store_types')->insert([
            'name' => 'Asda',
            'currency' => 'Pounds',
            'small_logo' => 'logos/Asda_Asda_large.jpg',
            'large_logo' => 'logos/Asda_Asda_small.jpg',
            'user_id' => 1
        ]);

        DB::table('store_types')->insert([
            'name' => 'Real Canadian Superstre',
            'currency' => 'Canadian Dollars',
            'small_logo' => 'logos/Asda_Asda_large.jpg',
            'large_logo' => 'logos/Asda_Asda_small.jpg',
            'user_id' => 2
        ]);
        
    }
}
