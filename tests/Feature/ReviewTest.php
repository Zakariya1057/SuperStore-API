<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testReviewsResponse()
    {
        $response = $this->get('/api/product/1/reviews');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            '*' => [
                'id',
                'title',
                'text',
                'image',
                'rating',
                'product_id',
                'user_id',
                'site_review_id',
                'created_at',
                'updated_at',
                'name',
            ]
        ]]);
    }

        /**
     * A basic feature test example.
     *
     * @return void
    */
    public function testReviewCreateResponse()
    {
        $user = factory(User::class)->make();

        $response = $this->actingAs($user)->postJson('/api/product/1/review/delete');

        $response->assertStatus(200)->assertJson(['data' => ['status' => 'success'] ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
    */
    public function testReviewDeleteResponse()
    {
        $user = factory(User::class)->make();

        $response = $this->actingAs($user)->postJson('/api/product/1/review/delete');

        $response->assertStatus(200)->assertJson(['data' => ['status' => 'success'] ]);
    }
}
