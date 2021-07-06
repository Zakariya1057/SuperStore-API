<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupermarketChainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('supermarket_chains')->insert([
            'name' => 'Real Canadian Superstore',
            'currency' => 'Canadian Dollars',
            'user_id' => 2,
            'company_id' => 1
        ]);

        DB::table('supermarket_chains')->insert([
            'name' => 'No Frills',
            'currency' => 'Canadian Dollars',
            'user_id' => 2,
            'company_id' => 1
        ]);
    }
}
