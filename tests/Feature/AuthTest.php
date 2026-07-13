<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration successfully.
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '081234567890',
            'password' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'phone_number',
                             'role',
                             'created_at',
                             'updated_at',
                         ],
                         'token',
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'phone_number' => '081234567890',
            'role' => 'customer',
        ]);
    }

    /**
     * Test user registration validation rules.
     */
    public function test_user_registration_requires_valid_data()
    {
        // 1. Min password length is 8
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '081234567890',
            'password' => 'short',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);

        // 2. Role must be super_admin, venue_owner, or customer
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '081234567890',
            'password' => 'password123',
            'role' => 'invalid_role',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['role']);

        // 3. Unique email and unique phone_number
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'phone_number' => '089999999999',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'phone_number' => '089999999999',
            'password' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'phone_number']);
    }

    /**
     * Test user login.
     */
    public function test_user_can_login()
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '081234567891',
            'password' => bcrypt('password123'),
            'role' => 'venue_owner',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'phone_number',
                             'role',
                         ],
                         'token',
                     ],
                 ]);
    }

    /**
     * Test login validation with wrong credentials.
     */
    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '081234567891',
            'password' => bcrypt('password123'),
            'role' => 'venue_owner',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test logout.
     */
    public function test_authenticated_user_can_logout()
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '081234567891',
            'password' => bcrypt('password123'),
            'role' => 'venue_owner',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logged out successfully.',
                     'data' => null,
                 ]);

        // Reset resolved auth instances so Laravel re-authenticates the next request
        $this->app['auth']->forgetGuards();

        // Accessing me endpoint with deleted token should fail
        $responseMe = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/me');

        $responseMe->assertStatus(401);
    }

    /**
     * Test get profile.
     */
    public function test_authenticated_user_can_fetch_profile()
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '081234567891',
            'password' => bcrypt('password123'),
            'role' => 'venue_owner',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/me');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'user' => [
                             'id' => $user->id,
                             'name' => 'Jane Doe',
                             'email' => 'jane@example.com',
                             'role' => 'venue_owner',
                         ]
                     ]
                 ]);
    }

    /**
     * Test profile retrieval without authentication is blocked.
     */
    public function test_guest_cannot_fetch_profile()
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
    }
}
