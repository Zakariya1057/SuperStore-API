<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testListUnauthorised()
    {
        $response = $this->getJson('/api/list/stores/2');
        $response->assertUnauthorized()->assertSee('Unauthenticated');
    }

        /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAuthListAllResponse()
    {
        $user = factory(User::class)->make();

        $response = $this->actingAs($user)->getJson('/api/list/stores/2');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            '*' => [
                'id',
                'identifier',
                'name',
                'status',
                'user_id',
                'total_price',
                'ticked_off_items',
                'total_items',
                'old_total_price',
                'currency',
                'supermarket_chain_id',
                'updated_at',
                'created_at',
            ]

        ]]);
    }


    public function testCreateListResponse()
    {
        $user = factory(User::class)->make();

        $response = $this->actingAs($user)->postJson('/api/list/create/', [
            'data' => [
                'name' => 'Names',
                'supermarket_chain_id' => 1, 
                'identifier' => '00F7301B-4607-43B0-94E6-362EB496A282'
            ]
        ]);

        $response->assertStatus(200);
    }


        /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAuthListResponse()
    {
        $user = factory(User::class)->make();

        $user->id = 1;

        $response = $this->actingAs($user)->get('/api/list/1');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'id',
            'identifier',
            'name',
            'status',
            'user_id',
            'total_price',
            'ticked_off_items',
            'total_items',
            'old_total_price',
            'categories',
            'currency',
            'categories' => [
                '*' => [
                    'id',
                    'name',
                    'items' => [
                        '*' => [
                            'id',
                            'name',
                            'product_id',
                            'total_price',
                            'price',
                            'currency',
                            'quantity',
                            'weight',
                            'small_image',
                            'large_image',
                            'ticked_off'
                        ]
                    ]
                ]
            ],
            'supermarket_chain_id',
            'updated_at',
            'created_at',
        ]]);
    }
}
