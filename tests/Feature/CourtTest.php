<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venue;
use App\Models\Court;
use App\Models\CourtImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourtTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $otherOwner;
    protected Venue $venue;

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

        // Create venue belonging to owner
        $this->venue = Venue::create([
            'name' => 'GOR Bulutangkis Sejahtera',
            'slug' => 'gor-bulutangkis-sejahtera',
            'address' => 'Jl. Sejahtera No. 10',
            'city' => 'Surabaya',
            'commission_rate' => 0.10,
            'status' => 'active',
        ]);
        $this->venue->users()->attach($this->owner->id, ['role' => 'owner']);
    }

    /**
     * Test list courts in a venue.
     */
    public function test_owner_can_list_courts_of_their_venue()
    {
        $court = Court::create([
            'venue_id' => $this->venue->id,
            'name' => 'Lapangan A',
            'category' => 'badminton',
            'price_per_hour' => 50000.00,
            'status' => 'active',
        ]);

        $token = $this->owner->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/venues/' . $this->venue->id . '/courts');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.id', $court->id);
    }

    /**
     * Test list courts IDOR protection.
     */
    public function test_other_owner_cannot_list_courts_of_venue()
    {
        $token = $this->otherOwner->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/venues/' . $this->venue->id . '/courts');

        $response->assertStatus(403);
    }

    /**
     * Test create court with images upload.
     */
    public function test_owner_can_create_court_with_images()
    {
        Storage::fake('public');
        $image1 = UploadedFile::fake()->image('court_photo1.jpg');
        $image2 = UploadedFile::fake()->image('court_photo2.jpg');

        $token = $this->owner->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/courts', [
            'venue_id' => $this->venue->id,
            'name' => 'Lapangan B',
            'category' => 'futsal',
            'price_per_hour' => 150000.00,
            'status' => 'active',
            'images' => [$image1, $image2],
            'primary_image_index' => 0, // Gambar 1 adalah foto utama
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Lapangan B')
                 ->assertJsonCount(2, 'data.images')
                 ->assertJsonPath('data.images.0.is_primary', true)
                 ->assertJsonPath('data.images.1.is_primary', false);

        $this->assertDatabaseHas('courts', [
            'name' => 'Lapangan B',
            'category' => 'futsal',
        ]);
    }

    /**
     * Test delete court deletes its files from storage.
     */
    public function test_delete_court_deletes_physical_images()
    {
        Storage::fake('public');
        
        $court = Court::create([
            'venue_id' => $this->venue->id,
            'name' => 'Lapangan C',
            'category' => 'tennis',
            'price_per_hour' => 100000.00,
            'status' => 'active',
        ]);

        // Upload an image
        $imageFile = UploadedFile::fake()->image('court_c.jpg');
        $path = $imageFile->store('courts', 'public');
        
        $courtImage = CourtImage::create([
            'court_id' => $court->id,
            'image_path' => $path,
            'is_primary' => true,
        ]);

        // Delete court
        $token = $this->owner->createToken('auth_token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/courts/' . $court->id);

        $response->assertStatus(200);

        // Verify database records are gone
        $this->assertDatabaseMissing('courts', ['id' => $court->id]);
        $this->assertDatabaseMissing('court_images', ['id' => $courtImage->id]);

        // Verify physical file is deleted
        Storage::disk('public')->assertMissing($path);
    }
}
