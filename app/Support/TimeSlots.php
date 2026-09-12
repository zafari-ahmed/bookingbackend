<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

class TimeSlots
{
    public const GRID_MINUTES = 30;

    /**
     * @return list<string>
     */
    public static function generate(string $open, string $close, int $minutes): array
    {
        $open = self::label($open);
        $close = self::label($close);
        $slots = [];
        $cursor = Carbon::parse($open);
        $end = Carbon::parse($close);

        if ($end->lte($cursor)) {
            $end->addDay();
        }

        while ($cursor->lt($end)) {
            $slots[] = $cursor->format('H:i');
            $cursor->addMinutes($minutes);
        }

        return $slots;
    }

    public static function isOvernight(string $open, string $close): bool
    {
        return self::label($close) <= self::label($open);
    }

    public static function isMorningSlot(string $time, string $open, string $close): bool
    {
        $time = self::label($time);
        $close = self::label($close);

        return self::isOvernight($open, $close) && $time < $close;
    }

    public static function inHours(string $time, string $open, string $close, bool $allowClose = false): bool
    {
        $time = self::label($time);
        $open = self::label($open);
        $close = self::label($close);

        if (! self::isOvernight($open, $close)) {
            return $allowClose
                ? $time >= $open && $time <= $close
                : $time >= $open && $time < $close;
        }

        if ($time >= $open) {
            return true;
        }

        return $allowClose ? $time <= $close : $time < $close;
    }

    public static function covers(string $start, string $end, string $slot): bool
    {
        $start = self::label($start);
        $end = self::label($end);
        $slot = self::label($slot);

        if ($end > $start) {
            return $start <= $slot && $slot < $end;
        }

        return $slot >= $start || $slot < $end;
    }

    public static function span(string $start, string $end, int $minutes): int
    {
        return max(1, (int) ceil(self::durationMinutes($start, $end) / $minutes));
    }

    public static function durationMinutes(string $start, string $end): int
    {
        [$from, $to] = self::period($start, $end);

        return (int) $from->diffInMinutes($to);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function period(string $start, string $end): array
    {
        $from = Carbon::parse(self::label($start));
        $to = Carbon::parse(self::label($end));

        if ($to->lte($from)) {
            $to->addDay();
        }

        return [$from, $to];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dateRange(string $date, string $start, string $end): array
    {
        $from = Carbon::parse($date.' '.self::label($start));
        $to = Carbon::parse($date.' '.self::label($end));

        if ($to->lte($from)) {
            $to->addDay();
        }

        return [$from, $to];
    }

    public static function rangesOverlap(array $left, array $right): bool
    {
        return $left[0]->lt($right[1]) && $right[0]->lt($left[1]);
    }

    public static function clockDate(string $sessionDate, string $start, string $open, string $close): string
    {
        $date = Carbon::parse($sessionDate);

        if (self::isMorningSlot($start, $open, $close)) {
            $date->addDay();
        }

        return $date->toDateString();
    }

    public static function sessionDate(string $clockDate, string $start, string $open, string $close): string
    {
        $date = Carbon::parse($clockDate);

        if (self::isMorningSlot($start, $open, $close)) {
            $date->subDay();
        }

        return $date->toDateString();
    }

    public static function sessionMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', self::label($time)));
        $minutes = ($hour * 60) + $minute;

        return $minutes < 12 * 60 ? $minutes + 24 * 60 : $minutes;
    }

    /**
     * @param  iterable<int, object|array>  $courts
     * @return list<string>
     */
    public static function generateForCourts(iterable $courts, int $minutes): array
    {
        $unique = [];

        foreach ($courts as $court) {
            $open = self::label(data_get($court, 'opening_time', '17:00'));
            $close = self::label(data_get($court, 'closing_time', '03:00'));

            foreach (self::generate($open, $close, $minutes) as $slot) {
                $unique[$slot] = true;
            }
        }

        $slots = array_keys($unique);
        usort($slots, fn (string $a, string $b) => self::sessionMinutes($a) <=> self::sessionMinutes($b));

        return $slots;
    }

    public static function label(string|DateTimeInterface|null $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        return substr((string) $value, 0, 5);
    }
}
