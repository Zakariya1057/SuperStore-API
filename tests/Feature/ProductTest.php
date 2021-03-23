<?php

namespace Tests\Feature;

use App\Models\User;
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
            'small_image',
            'large_image',
            'images',
            
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
            'favourite',
            'monitoring',
            
            'reviews',
            'ingredients',
            
            'recommended',
            
            'parent_category_id',
            'parent_category_name',
            
            'promotion',
        ]]);
    }


    public function testFavouriteProducts(){
        $user = factory(User::class)->make();
        
        $response = $this->actingAs($user)->get('/api/favourites');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            '*' => [
                'id',
                'name',
                'small_image',
                'large_image',
                'images',
                
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
                'favourite',
                'monitoring',
                
                'reviews',
                'ingredients',
                
                'recommended',
                
                'parent_category_id',
                'parent_category_name',
                
                'promotion',
            ]

        ]]);

    }
}
