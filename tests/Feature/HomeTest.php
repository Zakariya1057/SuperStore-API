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
        $response = $this->get('/api/home');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'groceries',
            'lists',
            'featured',
            'stores',
            'categories',
            'promotions',
        ]]);
    }
    
}
