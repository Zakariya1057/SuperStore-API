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
            'id' => 8,
            'name' => 'Ontario',
            'country' => 'Canada',
            'company_id' => 1
        ]);
 
        DB::table('regions')->insert([
            'id' => 9,
            'name' => 'Alberta',
            'country' => 'Canada',
            'company_id' => 1
        ]);

        DB::table('regions')->insert([
            'id' => 10,
            'name' => 'Manitoba',
            'country' => 'Canada',
            'company_id' => 1
        ]);

        DB::table('regions')->insert([
            'id' => 11,
            'name' => 'British Columbia',
            'country' => 'Canada',
            'company_id' => 1
        ]);

        DB::table('regions')->insert([
            'id' => 12,
            'name' => 'Saskatchewan',
            'country' => 'Canada',
            'company_id' => 1
        ]);

        DB::table('regions')->insert([
            'id' => 13,
            'name' => 'Yukon',
            'country' => 'Canada',
            'company_id' => 1
        ]);
    }
}
