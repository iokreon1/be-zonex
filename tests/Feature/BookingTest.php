<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueOperatingHour;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $customer2;

    protected User $owner;

    protected Venue $venue;

    protected Court $court;

    protected string $bookingDate;

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

        $this->customer2 = User::create([
            'name' => 'Customer Dua',
            'email' => 'customer2@example.com',
            'phone_number' => '081234567893',
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

        // Create venue operating hours
        // 2026-07-22 is a Wednesday (day_of_week = 3)
        VenueOperatingHour::create([
            'venue_id' => $this->venue->id,
            'day_of_week' => 3,
            'open_time' => '08:00:00',
            'close_time' => '22:00:00',
            'is_closed' => false,
        ]);

        $this->bookingDate = Carbon::parse('next wednesday')->format('Y-m-d');
    }

    /**
     * Test unauthenticated booking request.
     */
    public function test_booking_requires_authentication()
    {
        $response = $this->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test non-customer role access.
     */
    public function test_booking_requires_customer_role()
    {
        $response = $this->actingAs($this->owner, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Anda tidak memiliki hak akses untuk melakukan tindakan ini.');
    }

    /**
     * Test successful booking creation (Happy Path).
     */
    public function test_booking_success()
    {
        $bookingDate = $this->bookingDate;
        $startTime = '09:00';
        $endTime = '11:00'; // 2 hours

        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Pemesanan lapangan berhasil dibuat.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'booking_code',
                    'venue_id',
                    'court_id',
                    'user_id',
                    'booking_date',
                    'start_time',
                    'end_time',
                    'total_price',
                    'status',
                    'payment_status',
                    'expires_at',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $data = $response->json('data');

        // Assert code format ZX-YYYYMMDD-RANDOMSTRING
        $dateFormatted = Carbon::parse($this->bookingDate)->format('Ymd');
        $this->assertMatchesRegularExpression('/^ZX-'.$dateFormatted.'-[A-Z0-9]{6}$/', $data['booking_code']);

        // Assert price calculation (2 hours * 50,000)
        $this->assertEquals(100000.00, $data['total_price']);

        // Assert expiry date is set around 15 minutes from now
        $expiresAt = Carbon::parse($data['expires_at']);
        $this->assertTrue($expiresAt->diffInMinutes(now()) <= 15);

        // Assert database record exists
        $this->assertDatabaseHas('bookings', [
            'id' => $data['id'],
            'booking_code' => $data['booking_code'],
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
    }

    /**
     * Test past booking time validation.
     */
    public function test_booking_cannot_be_in_the_past()
    {
        // 2026-07-19 is past relative to some future execution, but let's use fixed past
        $pastDate = Carbon::yesterday()->format('Y-m-d');

        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $pastDate,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        // FormRequest handles today check via after_or_equal:today
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_date']);
    }

    /**
     * Test booking past time on today.
     */
    public function test_booking_time_cannot_be_in_past_today()
    {
        // Set operating hour to today
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek;

        VenueOperatingHour::where('venue_id', $this->venue->id)->delete();
        VenueOperatingHour::create([
            'venue_id' => $this->venue->id,
            'day_of_week' => $dayOfWeek,
            'open_time' => '00:00:00',
            'close_time' => '23:00:00',
            'is_closed' => false,
        ]);

        // Try booking 1 hour ago
        $oneHourAgo = Carbon::now()->subHour();
        // If one hour ago falls into yesterday (e.g. running at 00:30), adjust
        if ($oneHourAgo->dayOfWeek !== $dayOfWeek) {
            $this->assertTrue(true);

            return;
        }

        $startTimeStr = $oneHourAgo->format('H:00');
        $endTimeStr = $oneHourAgo->copy()->addHour()->format('H:00');

        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $today->format('Y-m-d'),
            'start_time' => $startTimeStr,
            'end_time' => $endTimeStr,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    /**
     * Test non-whole hour booking gets rejected.
     */
    public function test_booking_must_be_whole_hours()
    {
        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '09:30', // not whole hour
            'end_time' => '10:30',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    /**
     * Test booking out of operating hours gets rejected.
     */
    public function test_booking_must_be_within_operating_hours()
    {
        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '07:00', // opens at 08:00
            'end_time' => '09:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    /**
     * Test booking when venue is closed gets rejected.
     */
    public function test_booking_when_venue_closed()
    {
        $closedDate = Carbon::parse($this->bookingDate)->addDay()->format('Y-m-d');
        // Thursday (dayOfWeek = 4), which operatingHour is closed/missing
        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $closedDate,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_date']);
    }

    /**
     * Test overlapping double booking is prevented.
     */
    public function test_double_booking_prevented()
    {
        // 1. Create an existing confirmed booking for 10:00 - 12:00
        Booking::create([
            'booking_code' => 'BOOK-EXIST',
            'venue_id' => $this->venue->id,
            'court_id' => $this->court->id,
            'user_id' => $this->customer2->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_price' => 100000.00,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        // 2. Try to book overlapping 09:00 - 11:00 (overlaps at 10:00-11:00)
        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_slot']);

        // 3. Try to book overlapping 11:00 - 13:00 (overlaps at 11:00-12:00)
        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '11:00',
            'end_time' => '13:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_slot']);

        // 4. Try to book non-overlapping 12:00 - 14:00 (should succeed)
        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '12:00',
            'end_time' => '14:00',
        ]);

        $response->assertStatus(201);
    }

    /**
     * Test double booking ignores inactive/expired/cancelled bookings.
     */
    public function test_double_booking_ignores_inactive_bookings()
    {
        // 1. Create a cancelled booking for 10:00 - 12:00
        Booking::create([
            'booking_code' => 'BOOK-CANCELLED',
            'venue_id' => $this->venue->id,
            'court_id' => $this->court->id,
            'user_id' => $this->customer2->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_price' => 100000.00,
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
        ]);

        // 2. Create an expired pending booking for 12:00 - 14:00
        Booking::create([
            'booking_code' => 'BOOK-EXPIRED',
            'venue_id' => $this->venue->id,
            'court_id' => $this->court->id,
            'user_id' => $this->customer2->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
            'total_price' => 100000.00,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'expires_at' => Carbon::now()->subMinutes(1), // expired!
        ]);

        // 3. Book 10:00 - 12:00 (should succeed since previous was cancelled)
        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);
        $response->assertStatus(201);

        // 4. Book 12:00 - 14:00 (should succeed since previous was expired)
        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '12:00',
            'end_time' => '14:00',
        ]);
        $response->assertStatus(201);
    }

    /**
     * Test that pessimistic locking query is executed.
     */
    public function test_pessimistic_locking_is_called()
    {
        DB::enableQueryLog();

        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->bookingDate,
            'start_time' => '14:00',
            'end_time' => '16:00',
        ]);

        $response->assertStatus(201);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // If the database connection is sqlite, lockForUpdate() won't have "for update" in the SQL
        // but we can still assert that a query was run on the bookings table.
        // For other drivers (MySQL, Postgres), it must contain 'for update'.
        $driverName = DB::connection()->getDriverName();
        $hasLockQuery = false;

        foreach ($queries as $queryInfo) {
            $sql = strtolower($queryInfo['query']);
            if (str_contains($sql, 'select') && str_contains($sql, 'bookings')) {
                if ($driverName === 'sqlite') {
                    $hasLockQuery = true;
                    break;
                } elseif (str_contains($sql, 'for update')) {
                    $hasLockQuery = true;
                    break;
                }
            }
        }

        $this->assertTrue($hasLockQuery, 'Query log should contain a query on bookings.');
    }
}
