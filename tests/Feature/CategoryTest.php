<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCategoryResponse()
    {
        $response = $this->get('/api/grocery/1');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            '*' => [
                'id',
                'name',
                'child_categories' => [
                    '*' => [
                        'id',
                        'name',
                        'parent_category_id',
                        'site_category_id',
                        'store_type_id',
                        'store_id',
                    ]
                ]
            ]
        ]]);
    }

        /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCategoryProductsResponse()
    {
        $response = $this->get('/api/grocery/products/1');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            '*' => [
                'id',
                'name',
                'parent_category_id',
                'products' => [
                    '*' => [
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
                        'promotion_id',
                    ]
                ]
            ]
        ]]);
    }
}
