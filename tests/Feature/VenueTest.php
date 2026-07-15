<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VenueTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $otherOwner;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create testing users
        $this->owner = User::create([
            'name' => 'Owner Satu',
            'email' => 'owner1@example.com',
            'phone_number' => '081234567891',
            'password' => bcrypt('password123'),
            'role' => 'venue_owner',
        ]);

        $this->otherOwner = User::create([
            'name' => 'Owner Dua',
            'email' => 'owner2@example.com',
            'phone_number' => '081234567892',
            'password' => bcrypt('password123'),
            'role' => 'venue_owner',
        ]);

        $this->customer = User::create([
            'name' => 'Customer Satu',
            'email' => 'customer1@example.com',
            'phone_number' => '081234567893',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);
    }

    /**
     * Test list venues only returns the authenticated owner's venues.
     */
    public function test_owner_can_only_see_their_own_venues()
    {
        // Owner 1 creates a venue
        $venue1 = Venue::create([
            'name' => 'Gedung Olahraga Jaya',
            'slug' => 'gedung-olahraga-jaya',
            'address' => 'Jl. Jaya No. 12',
            'city' => 'Jakarta',
            'commission_rate' => 0.10,
            'status' => 'active',
        ]);
        $venue1->users()->attach($this->owner->id, ['role' => 'owner']);

        // Owner 2 creates a venue
        $venue2 = Venue::create([
            'name' => 'GOR Sentosa',
            'slug' => 'gor-sentosa',
            'address' => 'Jl. Sentosa No. 5',
            'city' => 'Bandung',
            'commission_rate' => 0.10,
            'status' => 'active',
        ]);
        $venue2->users()->attach($this->otherOwner->id, ['role' => 'owner']);

        // Request as Owner 1
        $token = $this->owner->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/venues');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.id', $venue1->id);
    }

    /**
     * Test customer cannot see list venues CRUD.
     */
    public function test_customer_cannot_access_venues_crud()
    {
        $token = $this->customer->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/venues');

        $response->assertStatus(403);
    }

    /**
     * Test owner can create venue with default operating hours.
     */
    public function test_owner_can_create_venue()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('venue.jpg');

        $token = $this->owner->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/venues', [
            'name' => 'GOR Bulutangkis Sejahtera',
            'address' => 'Jl. Sejahtera No. 10',
            'city' => 'Surabaya',
            'latitude' => -7.2575,
            'longitude' => 112.7521,
            'featured_image' => $file,
            'bank_account' => '1234567890',
            'commission_rate' => 0.10,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'GOR Bulutangkis Sejahtera')
                 ->assertJsonCount(7, 'data.operating_hours'); // 7 days of the week

        $this->assertDatabaseHas('venues', [
            'name' => 'GOR Bulutangkis Sejahtera',
            'city' => 'Surabaya',
        ]);
    }

    /**
     * Test IDOR protection for showing a venue.
     */
    public function test_owner_cannot_view_others_venue()
    {
        $venue = Venue::create([
            'name' => 'GOR Sentosa',
            'slug' => 'gor-sentosa',
            'address' => 'Jl. Sentosa No. 5',
            'city' => 'Bandung',
            'commission_rate' => 0.10,
            'status' => 'active',
        ]);
        $venue->users()->attach($this->otherOwner->id, ['role' => 'owner']);

        // Request GOR Sentosa as Owner 1
        $token = $this->owner->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/venues/' . $venue->id);

        $response->assertStatus(403);
    }

    /**
     * Test owner can update operating hours with logical validations.
     */
    public function test_owner_can_update_operating_hours()
    {
        $venue = Venue::create([
            'name' => 'Gedung Olahraga Jaya',
            'slug' => 'gedung-olahraga-jaya',
            'address' => 'Jl. Jaya No. 12',
            'city' => 'Jakarta',
            'commission_rate' => 0.10,
            'status' => 'active',
        ]);
        $venue->users()->attach($this->owner->id, ['role' => 'owner']);

        $token = $this->owner->createToken('auth_token')->plainTextToken;

        // 1. Valid update
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/venues/' . $venue->id . '/operating-hours', [
            'hours' => [
                [
                    'day_of_week' => 0, // Minggu
                    'is_closed' => false,
                    'open_time' => '07:00',
                    'close_time' => '21:00',
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('venue_operating_hours', [
            'venue_id' => $venue->id,
            'day_of_week' => 0,
            'open_time' => '07:00:00',
            'close_time' => '21:00:00',
            'is_closed' => 0,
        ]);

        // 2. Invalid update: close_time before open_time
        $responseInvalid = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/venues/' . $venue->id . '/operating-hours', [
            'hours' => [
                [
                    'day_of_week' => 1, // Senin
                    'is_closed' => false,
                    'open_time' => '17:00',
                    'close_time' => '08:00', // salah
                ]
            ]
        ]);

        $responseInvalid->assertStatus(422)
                        ->assertJsonValidationErrors(['hours.0.close_time']);
    }
}
