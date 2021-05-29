<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('regions')->insert([
            'name' => 'Onatario',
            'country' => 'Canada',
            'store_type_id' => 2
        ]);
 
        DB::table('regions')->insert([
            'name' => 'Alberta',
            'country' => 'Canada',
            'store_type_id' => 2
        ]);

        DB::table('regions')->insert([
            'name' => 'Manitoba',
            'country' => 'Canada',
            'store_type_id' => 2
        ]);

        DB::table('regions')->insert([
            'name' => 'British Columbia',
            'country' => 'Canada',
            'store_type_id' => 2
        ]);

        DB::table('regions')->insert([
            'name' => 'Saskatchewan',
            'country' => 'Canada',
            'store_type_id' => 2
        ]);

        DB::table('regions')->insert([
            'name' => 'Yukon',
            'country' => 'Canada',
            'store_type_id' => 2
        ]);
    }
}
