<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HomeTest extends TestCase
{
    /**
     * A basic feature to test home json.
     *
     * @return void
     */
    public function testHomeResponse(){
        $response = $this->postJson('/api/home', [
            'data' => [
                'latitude' => 43.6532,
                'longitude' => -79.3832,
                'supermarket_chain_id' => 2
            ]
        ]);

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'lists',
            'stores',
            'featured',
            'groceries',
            'monitoring',
            'promotions',
            'on_sale',
            'categories'
        ]]);
    }
    
}
