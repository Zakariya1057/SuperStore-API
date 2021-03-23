<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StoreTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testStoreResponse(){
        $response = $this->get('/api/store/1');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'id',
            'name',
            'description',
            'store_image',
            'google_url',
            'uber_url',
            'url',
            'last_checked',
            'site_store_id',
            'store_type_id',
            'created_at',
            'updated_at',
            'large_logo',
            'small_logo',
            'location',
            'opening_hours',
            'facilities',
        ]]);
    }
}
