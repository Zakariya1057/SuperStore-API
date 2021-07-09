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

        // Add New Ones
        DB::table('regions')->insert([
            'id' => 14,
            'name' => 'Newfoundland and Labrador',
            'country' => 'Canada',
            'company_id' => 1
        ]);

        DB::table('regions')->insert([
            'id' => 15,
            'name' => 'Nova Scotia',
            'country' => 'Canada',
            'company_id' => 1
        ]);

        DB::table('regions')->insert([
            'id' => 16,
            'name' => 'Prince Edward Island',
            'country' => 'Canada',
            'company_id' => 1
        ]);

        DB::table('regions')->insert([
            'id' => 17,
            'name' => 'New Brunswick',
            'country' => 'Canada',
            'company_id' => 1
        ]);

        DB::table('regions')->insert([
            'id' => 18,
            'name' => 'Quebec',
            'country' => 'Canada',
            'company_id' => 1
        ]);
    }
}
