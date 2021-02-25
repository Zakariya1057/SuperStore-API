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
        $response = $this->getJson('/api/list');
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

        $response = $this->actingAs($user)->getJson('/api/list');

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            '*' => [
                'id',
                'identifier',
                'name',
                'status',
                'store_id',
                'user_id',
                'total_price',
                'ticked_off_items',
                'total_items',
                'old_total_price',
                'categories',
                'updated_at',
                'created_at',
            ]

        ]]);
    }
}
