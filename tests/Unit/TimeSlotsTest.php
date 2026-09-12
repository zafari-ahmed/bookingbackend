<?php

namespace Tests\Unit;

use App\Support\TimeSlots;
use PHPUnit\Framework\TestCase;

class TimeSlotsTest extends TestCase
{
    public function test_overnight_hours_generate_evening_then_morning_slots(): void
    {
        $slots = TimeSlots::generate('17:00', '03:00', 30);

        $this->assertSame('17:00', $slots[0]);
        $this->assertContains('23:30', $slots);
        $this->assertContains('00:00', $slots);
        $this->assertContains('02:30', $slots);
        $this->assertNotContains('03:00', $slots);
        $this->assertSame('02:30', end($slots));
    }

    public function test_duration_wraps_past_midnight(): void
    {
        $this->assertSame(60, TimeSlots::durationMinutes('23:00', '00:00'));
        $this->assertSame(90, TimeSlots::durationMinutes('23:00', '00:30'));
        $this->assertSame(120, TimeSlots::durationMinutes('01:00', '03:00'));
    }

    public function test_morning_bookings_move_to_the_next_calendar_day(): void
    {
        $this->assertSame('2026-09-13', TimeSlots::clockDate('2026-09-12', '01:00', '17:00', '03:00'));
        $this->assertSame('2026-09-12', TimeSlots::clockDate('2026-09-12', '18:00', '17:00', '03:00'));
        $this->assertSame('2026-09-12', TimeSlots::sessionDate('2026-09-13', '01:00', '17:00', '03:00'));
    }
}
