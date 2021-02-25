<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductTest extends TestCase
{
    /**
     * A basic feature to test home json.
     *
     * @return void
     */
    public function testProductResponse(){
        $response = $this->get('/api/product/1');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'id',
            'name',
            'large_image',
            'small_image',
            'description',
            'price',
            'old_price',
            'is_on_sale',
            'promotion_id',
            'weight' ,
            'brand',
            'storage',
            'dietary_info',
            'allergen_info',
            'avg_rating',
            'total_reviews_count',
            'store_type_id',
            'parent_category_id',
            'parent_category_name',
            'promotion',
            'favourite',
            'monitoring',
            'ingredients',
            'reviews',
        ]]);
    }
}
