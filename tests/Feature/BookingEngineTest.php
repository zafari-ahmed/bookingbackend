<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Member $member;
    private Court $court;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create(['role' => 'staff']);
        $sport = Sport::query()->create(['name' => 'Padel', 'slug' => 'padel', 'status' => 'active']);
        $this->court = Court::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel Court 1',
            'price_per_hour' => 5000,
            'opening_time' => '08:00',
            'closing_time' => '23:00',
            'status' => 'active',
        ]);
        $this->member = Member::query()->create([
            'name' => 'Ahmed Khan',
            'phone' => '03001234567',
            'member_number' => 'M-1024',
        ]);
    }

    public function test_staff_can_create_a_booking_and_calculate_payment(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/bookings', $this->payload([
            'total_amount' => 5000,
            'paid_amount' => 2000,
        ]));

        $response->assertOk()->assertJsonPath('booking.payment_status', 'partial_paid');
        $this->assertDatabaseHas('bookings', [
            'member_id' => $this->member->id,
            'remaining_amount' => 3000,
        ]);
    }

    public function test_overlapping_bookings_are_rejected(): void
    {
        $this->actingAs($this->staff)->postJson('/bookings', $this->payload())->assertOk();

        $this->actingAs($this->staff)->postJson('/bookings', $this->payload([
            'start_time' => '19:30',
            'end_time' => '20:30',
        ]))->assertStatus(409)->assertJsonPath('conflict', true);
    }

    public function test_member_search_recognizes_existing_phone(): void
    {
        $this->actingAs($this->staff)
            ->getJson('/api/members/search?q=03001234567')
            ->assertOk()
            ->assertJsonPath('members.0.member_number', 'M-1024');
    }

    public function test_staff_cannot_open_settings(): void
    {
        $this->actingAs($this->staff)->get('/settings')->assertForbidden();
    }

    public function test_manager_can_cancel_a_booking(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($this->staff)->postJson('/bookings', $this->payload())->assertOk();
        $booking = Booking::query()->first();

        $this->actingAs($manager)->postJson("/bookings/{$booking->id}/cancel", [
            'reason' => 'Member requested cancellation',
        ])->assertOk();

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->booking_status);
    }

    public function test_full_payment_sets_fully_paid_status(): void
    {
        $this->actingAs($this->staff)->postJson('/bookings', $this->payload([
            'paid_amount' => 5000,
        ]))->assertJsonPath('booking.payment_status', PaymentStatus::FullyPaid->value);
    }

    public function test_quote_scales_price_for_custom_duration(): void
    {
        $this->actingAs($this->staff)
            ->getJson('/bookings/quote/amount?court_id='.$this->court->id.'&start_time=08:30&end_time=10:00')
            ->assertOk()
            ->assertJsonPath('amount', 7500)
            ->assertJsonPath('hours', 1.5);
    }

    public function test_calendar_keeps_half_hour_gap_available(): void
    {
        $this->actingAs($this->staff)->postJson('/bookings', $this->payload([
            'start_time' => '08:30',
            'end_time' => '10:00',
            'total_amount' => 7500,
            'paid_amount' => 7500,
        ]))->assertOk();

        $cells = collect($this->actingAs($this->staff)
            ->getJson('/api/calendar?date=2026-09-12&view=day')
            ->assertOk()
            ->json('courts.0.cells'));

        $this->assertSame('available', $cells->firstWhere('slot', '08:00')['type']);
        $this->assertSame('booking', $cells->firstWhere('slot', '08:30')['type']);
        $this->assertSame(3, $cells->firstWhere('slot', '08:30')['span']);
        $this->assertSame('available', $cells->firstWhere('slot', '10:00')['type']);
    }

    public function test_overnight_court_hours_are_allowed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put("/courts/{$this->court->id}", [
            'sport_id' => $this->court->sport_id,
            'name' => $this->court->name,
            'price_per_hour' => 5000,
            'opening_time' => '17:00',
            'closing_time' => '03:00',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertSame('17:00:00', $this->court->fresh()->opening_time);
        $this->assertSame('03:00:00', $this->court->fresh()->closing_time);
    }

    public function test_booking_after_midnight_saves_on_the_next_day(): void
    {
        $this->court->update(['opening_time' => '17:00', 'closing_time' => '03:00']);

        $this->actingAs($this->staff)->postJson('/bookings', $this->payload([
            'booking_date' => '2026-09-12',
            'start_time' => '01:00',
            'end_time' => '02:00',
            'total_amount' => 5000,
            'paid_amount' => 5000,
        ]))->assertOk()->assertJsonPath('booking.date', '2026-09-13');

        $cells = collect($this->actingAs($this->staff)
            ->getJson('/api/calendar?date=2026-09-12&view=day')
            ->assertOk()
            ->json('courts.0.cells'));

        $this->assertSame('booking', $cells->firstWhere('slot', '01:00')['type']);
        $this->assertContains('02:30', $this->actingAs($this->staff)->getJson('/api/calendar?date=2026-09-12&view=day')->json('slots'));
    }

    public function test_booking_can_be_extended_into_an_available_gap(): void
    {
        $this->actingAs($this->staff)->postJson('/bookings', $this->payload([
            'start_time' => '08:30',
            'end_time' => '10:00',
            'total_amount' => 7500,
            'paid_amount' => 7500,
        ]))->assertOk();

        $booking = Booking::query()->first();

        $this->actingAs($this->staff)->putJson("/bookings/{$booking->id}", $this->payload([
            'start_time' => '08:00',
            'end_time' => '10:00',
            'total_amount' => 10000,
            'paid_amount' => 7500,
        ]))->assertOk()->assertJsonPath('booking.start_time', '08:00');

        $this->assertSame(120, $booking->fresh()->duration);
        $this->assertSame(10000.0, (float) $booking->fresh()->total_amount);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'member_id' => $this->member->id,
            'sport_id' => $this->court->sport_id,
            'court_id' => $this->court->id,
            'booking_date' => '2026-09-12',
            'start_time' => '19:00',
            'end_time' => '20:00',
            'players_count' => 4,
            'total_amount' => 5000,
            'paid_amount' => 5000,
            'payment_method' => 'cash',
        ], $overrides);
    }
}
