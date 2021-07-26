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
            'company_id' => 2
        ]);
 
        DB::table('regions')->insert([
            'id' => 9,
            'name' => 'Alberta',
            'country' => 'Canada',
            'company_id' => 2
        ]);

        DB::table('regions')->insert([
            'id' => 20,
            'name' => 'Manitoba',
            'country' => 'Canada',
            'company_id' => 2
        ]);

        DB::table('regions')->insert([
            'id' => 21,
            'name' => 'British Columbia',
            'country' => 'Canada',
            'company_id' => 2
        ]);

        DB::table('regions')->insert([
            'id' => 22,
            'name' => 'Saskatchewan',
            'country' => 'Canada',
            'company_id' => 2
        ]);

        DB::table('regions')->insert([
            'id' => 23,
            'name' => 'Yukon',
            'country' => 'Canada',
            'company_id' => 2
        ]);

        // Add New Ones
        DB::table('regions')->insert([
            'id' => 24,
            'name' => 'Newfoundland and Labrador',
            'country' => 'Canada',
            'company_id' => 2
        ]);

        DB::table('regions')->insert([
            'id' => 25,
            'name' => 'Nova Scotia',
            'country' => 'Canada',
            'company_id' => 2
        ]);

        DB::table('regions')->insert([
            'id' => 26,
            'name' => 'Prince Edward Island',
            'country' => 'Canada',
            'company_id' => 2
        ]);

        DB::table('regions')->insert([
            'id' => 27,
            'name' => 'New Brunswick',
            'country' => 'Canada',
            'company_id' => 2
        ]);

        DB::table('regions')->insert([
            'id' => 28,
            'name' => 'Quebec',
            'country' => 'Canada',
            'company_id' => 2
        ]);
    }
}
