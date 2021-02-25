<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    /**
     * A basic feature to test home json.
     *
     * @return void
     */
    public function testPromotionResponse(){
        $response = $this->get('/api/promotion/2');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'id',
            'name',
            'quantity',
            'price',
            'for_quantity',
            'products'
        ]]);
    }
}
