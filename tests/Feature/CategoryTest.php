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
        $response = $this->get('/api/grocery/categories/1');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            '*' => [
                'id',
                'name',
                'store_type_id',
                'parent_categories' => [
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
                'store_type_id',
                'products' => [
                    '*' => [
                        'id',
                        'name',
                        'small_image',
                        'large_image',
                        
                        'store_type_id',
                        
                        'description',
                        'features',
                        'dimensions',
                        
                        'price',
                        'old_price',
                        'is_on_sale',
                        'sale_ends_at',
                        
                        'currency',
                        
                        'storage',
                        'weight',
                        
                        'avg_rating',
                        'total_reviews_count',
                        
                        'dietary_info',
                        'allergen_info',
                        
                        'brand',
                        
                        'parent_category_id',
                        'parent_category_name',
                        'child_category_name',
                    ]
                ]
            ]
        ]]);
    }
}
