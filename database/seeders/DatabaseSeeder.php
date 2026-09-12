<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\BookingPayment;
use App\Models\Court;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\StaffNotification;
use App\Models\User;
use App\Support\TimeSlots;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->create([
            'name' => 'Zafar Ahmed',
            'email' => 'admin@sportavenue.club',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $manager = User::query()->create([
            'name' => 'Ayesha Malik',
            'email' => 'manager@sportavenue.club',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $staff = User::query()->create([
            'name' => 'Hassan Ali',
            'email' => 'staff@sportavenue.club',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => 'active',
        ]);

        foreach ([
            'club_name' => 'Sport Avenue Club',
            'slot_duration' => '30',
            'currency' => 'PKR',
            'default_open' => '08:00',
            'default_close' => '23:00',
        ] as $key => $value) {
            Setting::put($key, $value);
        }

        $catalog = [
            ['Padel', 'padel', '🎾', '#0EA5E9', [
                ['Padel Court 1', 5000],
                ['Padel Court 2', 5000],
                ['Padel Court 3', 4500],
            ]],
            ['Pickleball', 'pickleball', '🏓', '#84CC16', [
                ['Pickleball Court 1', 3500],
                ['Pickleball Court 2', 3500],
            ]],
            ['Tennis', 'tennis', '🏸', '#F59E0B', [
                ['Tennis Court 1', 4000],
                ['Tennis Court 2', 4000],
            ]],
            ['Badminton', 'badminton', '🏸', '#8B5CF6', [
                ['Badminton Court 1', 2500],
                ['Badminton Court 2', 2500],
            ]],
            ['Football', 'football', '⚽', '#16A34A', [['Football Ground', 8000]]],
            ['Cricket', 'cricket', '🏏', '#DC2626', [['Cricket Ground', 10000]]],
            ['Table Tennis', 'table-tennis', '🏓', '#2563EB', [['Table Tennis 1', 1500]]],
        ];

        $courts = collect();
        foreach ($catalog as $i => [$name, $slug, $icon, $color, $courtRows]) {
            $sport = Sport::query()->create([
                'name' => $name,
                'slug' => $slug,
                'description' => $name.' facilities at Sport Avenue Club',
                'icon' => $icon,
                'color' => $color,
                'status' => 'active',
                'sort_order' => $i + 1,
            ]);

            foreach ($courtRows as $j => [$courtName, $price]) {
                $courts->push(Court::query()->create([
                    'sport_id' => $sport->id,
                    'name' => $courtName,
                    'description' => 'Indoor '.$courtName,
                    'price_per_hour' => $price,
                    'opening_time' => '08:00',
                    'closing_time' => '23:00',
                    'status' => 'active',
                    'sort_order' => $j + 1,
                ]));
            }
        }

        $people = [
            ['Ahmed Khan', '03001234567', 'M-1024', 'male'],
            ['Sara Qureshi', '03019876543', 'M-1025', 'female'],
            ['Bilal Siddiqui', '03211234567', 'M-1026', 'male'],
            ['Fatima Noor', '03331239876', 'M-1027', 'female'],
            ['Usman Raza', '03451237890', 'M-1028', 'male'],
            ['Hira Sheikh', '03124567890', 'M-1029', 'female'],
            ['Omar Farooq', '03005551234', 'M-1030', 'male'],
            ['Nadia Iqbal', '03215550987', 'M-1031', 'female'],
            ['Zainab Ali', '03335550123', 'M-1032', 'female'],
            ['Hamza Tariq', '03415553456', 'M-1033', 'male'],
            ['Maryam Shah', '03007778899', 'M-1034', 'female'],
            ['Ali Haider', '03117776655', 'M-1035', 'male'],
            ['Sana Javed', '03216665544', 'M-1036', 'female'],
            ['Rehan Malik', '03314443322', 'M-1037', 'male'],
            ['Maham Aziz', '03412221100', 'M-1038', 'female'],
        ];

        $members = collect($people)->map(fn ($row) => Member::query()->create([
            'member_number' => $row[2],
            'name' => $row[0],
            'phone' => $row[1],
            'email' => strtolower(str_replace(' ', '.', $row[0])).'@mail.com',
            'gender' => $row[3],
            'dob' => Carbon::parse('1992-04-18')->addDays(rand(-2000, 2000)),
            'notes' => 'Regular club member',
            'status' => 'active',
        ]));

        $creators = [$admin, $manager, $staff];
        $today = Carbon::today();

        $plan = [];
        foreach (range(-10, 12) as $offset) {
            $date = $today->copy()->addDays($offset);
            foreach ($courts->take(9) as $index => $court) {
                $hours = $offset === 0 ? [8, 10, 13, 16, 18, 20] : [9, 16, 19];
                foreach ($hours as $hour) {
                    $plan[] = [$date->toDateString(), $court, $hour];
                }
            }
        }

        $statuses = [
            [BookingStatus::Confirmed, PaymentStatus::FullyPaid],
            [BookingStatus::Confirmed, PaymentStatus::PartialPaid],
            [BookingStatus::Confirmed, PaymentStatus::Pending],
            [BookingStatus::OnHold, PaymentStatus::Pending],
            [BookingStatus::Cancelled, PaymentStatus::Pending],
            [BookingStatus::Completed, PaymentStatus::FullyPaid],
        ];

        foreach ($plan as $i => [$date, $court, $hour]) {
            $member = $members[$i % $members->count()];
            $actor = $creators[$i % 3];
            [$bookingStatus, $paymentStatus] = $statuses[$i % count($statuses)];
            if ($date > $today->toDateString() && $bookingStatus === BookingStatus::Completed) {
                $bookingStatus = BookingStatus::Confirmed;
            }

            $start = sprintf('%02d:00', $hour);
            $end = sprintf('%02d:00', $hour + 1);
            $duration = TimeSlots::durationMinutes($start, $end);
            $total = round($court->price_per_hour * ($duration / 60));
            $paid = match ($paymentStatus) {
                PaymentStatus::FullyPaid => $total,
                PaymentStatus::PartialPaid => round($total * 0.5),
                default => 0,
            };

            $booking = Booking::query()->create([
                'member_id' => $member->id,
                'sport_id' => $court->sport_id,
                'court_id' => $court->id,
                'booking_date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'duration' => $duration,
                'players_count' => ($i % 2 === 0) ? 4 : 2,
                'total_amount' => $total,
                'paid_amount' => $bookingStatus === BookingStatus::Cancelled ? 0 : $paid,
                'booking_status' => $bookingStatus,
                'payment_method' => $paid > 0 ? ['cash', 'card', 'bank'][$i % 3] : null,
                'notes' => $i % 5 === 0 ? 'Member requested this court specifically.' : null,
                'created_by' => $actor->id,
                'cancelled_by' => $bookingStatus === BookingStatus::Cancelled ? $manager->id : null,
                'cancelled_at' => $bookingStatus === BookingStatus::Cancelled ? Carbon::parse($date)->setTime($hour, 0)->subHour() : null,
                'cancellation_reason' => $bookingStatus === BookingStatus::Cancelled ? 'Member requested cancellation' : null,
            ]);

            if ($booking->paid_amount > 0) {
                BookingPayment::query()->create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->paid_amount,
                    'payment_method' => $booking->payment_method ?? 'cash',
                    'payment_date' => Carbon::parse($date)->setTime($hour, 0)->subMinutes(20),
                    'received_by' => $actor->id,
                ]);
            }

            if ($bookingStatus === BookingStatus::OnHold) {
                BookingHold::query()->create([
                    'booking_id' => $booking->id,
                    'court_id' => $court->id,
                    'booking_date' => $date,
                    'start_time' => $start,
                    'end_time' => $end,
                    'member_id' => $member->id,
                    'reason' => 'Waiting for member confirmation',
                    'expires_at' => Carbon::parse($date)->setTime($hour, 0)->subMinutes(30),
                    'status' => HoldStatus::Active,
                    'notes' => 'Call back before expiry',
                    'created_by' => $manager->id,
                ]);
            }

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'booking.created',
                'entity_type' => Booking::class,
                'entity_id' => $booking->id,
                'description' => "{$actor->name} created booking {$booking->booking_number}",
                'created_at' => Carbon::parse($date)->setTime($hour, 0)->subMinutes(40),
            ]);
        }

        StaffNotification::query()->insert([
            [
                'type' => 'new_booking',
                'title' => 'New booking',
                'message' => 'Ahmed Khan booked Padel Court 1 at 07:00 PM.',
                'created_at' => now()->subMinutes(12),
                'updated_at' => now()->subMinutes(12),
            ],
            [
                'type' => 'payment_pending',
                'title' => 'Payment pending',
                'message' => 'Two evening bookings still have pending payments.',
                'created_at' => now()->subMinutes(40),
                'updated_at' => now()->subMinutes(40),
            ],
            [
                'type' => 'hold_expiring',
                'title' => 'Hold expiring',
                'message' => 'Padel Court 2 hold expires in 30 minutes.',
                'created_at' => now()->subMinutes(8),
                'updated_at' => now()->subMinutes(8),
            ],
            [
                'type' => 'upcoming_booking',
                'title' => 'Upcoming booking',
                'message' => 'Pickleball Court 1 starts in 20 minutes.',
                'created_at' => now()->subMinutes(5),
                'updated_at' => now()->subMinutes(5),
            ],
        ]);
    }
}
