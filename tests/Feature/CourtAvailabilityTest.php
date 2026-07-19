<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueOperatingHour;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $owner;

    protected Venue $venue;

    protected Court $court;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->owner = User::create([
            'name' => 'Owner Satu',
            'email' => 'owner1@example.com',
            'phone_number' => '081234567891',
            'password' => bcrypt('password123'),
            'role' => 'venue_owner',
        ]);

        $this->customer = User::create([
            'name' => 'Customer Satu',
            'email' => 'customer1@example.com',
            'phone_number' => '081234567892',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);

        // Create venue
        $this->venue = Venue::create([
            'name' => 'GOR Bulutangkis Sejahtera',
            'slug' => 'gor-bulutangkis-sejahtera',
            'address' => 'Jl. Sejahtera No. 10',
            'city' => 'Surabaya',
            'commission_rate' => 0.10,
            'status' => 'active',
        ]);
        $this->venue->users()->attach($this->owner->id, ['role' => 'owner']);

        // Create court
        $this->court = Court::create([
            'venue_id' => $this->venue->id,
            'name' => 'Lapangan A',
            'category' => 'badminton',
            'price_per_hour' => 50000.00,
            'status' => 'active',
        ]);
    }

    /**
     * Test validation of date parameter.
     */
    public function test_availability_requires_valid_date()
    {
        // Without date param
        $response = $this->getJson("/api/courts/{$this->court->id}/availability");
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);

        // Invalid date format
        $response = $this->getJson("/api/courts/{$this->court->id}/availability?date=15-07-2026");
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /**
     * Test availability when the venue is closed on the requested day.
     */
    public function test_availability_when_venue_closed()
    {
        // 2026-07-20 is a Monday (day_of_week = 1)
        VenueOperatingHour::create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 1,
            'open_time' => '08:00:00',
            'close_time' => '22:00:00',
            'is_closed' => true,
        ]);

        $response = $this->getJson("/api/courts/{$this->court->id}/availability?date=2026-07-20");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_closed', true)
            ->assertJsonCount(0, 'data.slots');
    }

    /**
     * Test availability when the venue is open.
     */
    public function test_availability_when_venue_open()
    {
        // 2026-07-22 is a Wednesday (day_of_week = 3)
        VenueOperatingHour::create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 3,
            'open_time' => '08:00:00',
            'close_time' => '12:00:00',
            'is_closed' => false,
        ]);

        $response = $this->getJson("/api/courts/{$this->court->id}/availability?date=2026-07-22");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_closed', false)
            ->assertJsonPath('data.open_time', '08:00')
            ->assertJsonPath('data.close_time', '12:00')
            ->assertJsonCount(4, 'data.slots')
            ->assertJsonPath('data.slots.0.start_time', '08:00')
            ->assertJsonPath('data.slots.0.end_time', '09:00')
            ->assertJsonPath('data.slots.0.is_booked', false);
    }

    /**
     * Test availability with active bookings.
     */
    public function test_availability_with_active_bookings()
    {
        // 2026-07-22 is a Wednesday (day_of_week = 3)
        VenueOperatingHour::create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 3,
            'open_time' => '08:00:00',
            'close_time' => '12:00:00',
            'is_closed' => false,
        ]);

        // Create confirmed booking for 09:00 - 10:00
        Booking::create([
            'booking_code' => 'BOOK001',
            'venue_id' => $this->venue->id,
            'court_id' => $this->court->id,
            'user_id' => $this->customer->id,
            'booking_date' => '2026-07-22',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'total_price' => 50000.00,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        // Create pending (active) booking for 11:00 - 12:00
        Booking::create([
            'booking_code' => 'BOOK002',
            'venue_id' => $this->venue->id,
            'court_id' => $this->court->id,
            'user_id' => $this->customer->id,
            'booking_date' => '2026-07-22',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'total_price' => 50000.00,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $response = $this->getJson("/api/courts/{$this->court->id}/availability?date=2026-07-22");

        $response->assertStatus(200)
            ->assertJsonPath('data.slots.0.start_time', '08:00')
            ->assertJsonPath('data.slots.0.is_booked', false) // 08:00 - 09:00 is free
            ->assertJsonPath('data.slots.1.start_time', '09:00')
            ->assertJsonPath('data.slots.1.is_booked', true)  // 09:00 - 10:00 is booked
            ->assertJsonPath('data.slots.2.start_time', '10:00')
            ->assertJsonPath('data.slots.2.is_booked', false) // 10:00 - 11:00 is free
            ->assertJsonPath('data.slots.3.start_time', '11:00')
            ->assertJsonPath('data.slots.3.is_booked', true);  // 11:00 - 12:00 is booked (pending with active reservation)
    }

    /**
     * Test availability ignores cancelled or expired bookings.
     */
    public function test_availability_ignores_inactive_bookings()
    {
        // 2026-07-22 is a Wednesday (day_of_week = 3)
        VenueOperatingHour::create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 3,
            'open_time' => '08:00:00',
            'close_time' => '10:00:00',
            'is_closed' => false,
        ]);

        // Create cancelled booking for 08:00 - 09:00
        Booking::create([
            'booking_code' => 'BOOK003',
            'venue_id' => $this->venue->id,
            'court_id' => $this->court->id,
            'user_id' => $this->customer->id,
            'booking_date' => '2026-07-22',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'total_price' => 50000.00,
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
        ]);

        // Create expired pending booking for 09:00 - 10:00
        Booking::create([
            'booking_code' => 'BOOK004',
            'venue_id' => $this->venue->id,
            'court_id' => $this->court->id,
            'user_id' => $this->customer->id,
            'booking_date' => '2026-07-22',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'total_price' => 50000.00,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'expires_at' => Carbon::now()->subMinutes(1), // expired!
        ]);

        $response = $this->getJson("/api/courts/{$this->court->id}/availability?date=2026-07-22");

        $response->assertStatus(200)
            ->assertJsonPath('data.slots.0.start_time', '08:00')
            ->assertJsonPath('data.slots.0.is_booked', false) // 08:00 - 09:00 is free (cancelled)
            ->assertJsonPath('data.slots.1.start_time', '09:00')
            ->assertJsonPath('data.slots.1.is_booked', false); // 09:00 - 10:00 is free (expired)
    }
}
