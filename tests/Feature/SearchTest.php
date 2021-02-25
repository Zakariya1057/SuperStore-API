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
        $response = $this->get('/api/search/suggestions/pasta');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'stores',
            'parent_categories',
            'child_categories',
            'products',
            'categories',
        ]]);
    }

    /**
     * A basic feature to test home json.
     *
     * @return void
     */
    public function testSearchResultsResponse(){
        $response = $this->post('api/search/results', [
            "data" => [
                "type" => "products",
                "detail" => "ASDA Pineapple Lolly"
            ]
        ]);

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'stores',
            'products',
            'filter',
            'products',
            'paginate',
        ]]);
    }
}
