<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testLoginFailedResponse()
    {
        $response = $this->postJson('/api/user/login', [
            'data' => [
                "email" => "email@email.com",
                "password" => "password12345",
                "notification_token" => ""
            ]
        ]);

        $response->assertStatus(403)->assertJson(['data' => ['error' => 'Incorrect password.'] ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
    */
    public function testLoginSuccessResponse()
    {
        // Change to apple testing account. Later
        $response = $this->postJson('/api/user/login', [
            'data' => [
                "email" => "email@email.com",
                "password" => "password123451",
                "notification_token" => ""
            ]
        ]);

        $response->assertStatus(200)->assertJsonStructure(['data' => [
            'id',
            'token',
            'name',
            'email',
            'send_notifications',
        ]]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
    */
    public function testSendResetCodeResponse()
    {
        // Change to apple testing account. Later
        $response = $this->postJson('/api/user/reset/send-code', [
            'data' => [
                "email" => "email@email.com",
            ]
        ]);

        $response->assertStatus(200)->assertJson(['data' => ['status' => 'success'] ]);
    }
}
