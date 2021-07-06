<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SearchTest extends TestCase
{
    /**
     * A basic feature to test home json.
     *
     * @return void
     */
    public function testSearchSuggestionsResponse(){
        $response = $this->postJson('/api/search/suggestions', ['data' => [
            'query' => 'Bread', 
            'supermarket_chain_id' => 2
        ]]);

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'stores',
            'store_sales',
            'child_categories',
            'parent_categories',
            'products',
            'promotions',
            'brands'
        ]]);
    }

    /**
     * A basic feature to test home json.
     *
     * @return void
     */
    public function testSearchProductResultsResponse(){
        $response = $this->post('/api/search/results/product?page=1', [
            'data' => [
                'supermarket_chain_id' => 2, 
                'type' => 'child_categories', 
                'dietary' => '', 
                'brand' => '', 
                'text_search' => false, 
                'sort' => '', 
                'order' => '', 
                'query' => 'Alcoholic Drinks', 
                'child_category' => ''
            ]
        ]);

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'products',
            'filter',
            'paginate'
        ]]);
    }


    /**
     * A basic feature to test home json.
     *
     * @return void
     */
    public function testSearchStoresResultsResponse(){
        $response = $this->post('/api/search/results/stores', [
            'data' => [
                "supermarket_chain_id" => 2,
                "latitude" => 43.6532,
                "longitude" => -79.3832
            ]
        ]);

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            '*' => [
                'id',
                'name',
                'description',
                'store_image',
                'google_url',
                'uber_url',
                'url',
                'last_checked',
                'site_store_id',
                'supermarket_chain_id',
                'created_at',
                'updated_at',
                'large_logo',
                'small_logo',
                'location',
                'opening_hours',
            ]
        ]]);
    }
}
