<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CaseStatus;
use App\Models\CaseComment;
use App\Models\CaseModel;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\CaseEscalatedNotification;
use App\Notifications\CaseResolvedNotification;
use App\Notifications\CommentAddedNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Gives the bell icon and Notifications page real content on first run.
 *
 * Notifications are sent on the database channel only and dispatched
 * synchronously — the point is a populated inbox, not exercising the queue.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $recipients = User::with('departments')->get();

        CaseModel::with(['department', 'comments.user', 'comments.department'])
            ->latest('created_at')
            ->take(12)
            ->get()
            ->each(function (CaseModel $case) use ($recipients): void {
                $audience = $this->audienceFor($case, $recipients);

                if ($audience->isEmpty()) {
                    return;
                }

                $this->send($audience, new CaseAssignedNotification($case, $case->department));

                match ($case->status) {
                    CaseStatus::Escalated => $this->send($audience, new CaseEscalatedNotification($case)),
                    CaseStatus::Resolved, CaseStatus::Closed => $this->send($audience, new CaseResolvedNotification($case)),
                    default => null,
                };

                $case->comments->take(2)->each(
                    fn (CaseComment $comment) => $this->send($audience, new CommentAddedNotification($comment))
                );
            });

        $this->staggerTimestamps();
        $this->markOlderAsRead();
    }

    /**
     * @param  Collection<int, User>  $audience
     */
    private function send($audience, object $notification): void
    {
        Notification::sendNow($audience, $notification, ['database']);
    }

    /**
     * Everyone who can see the case, plus the AC office.
     *
     * @param  Collection<int, User>  $recipients
     * @return Collection<int, User>
     */
    private function audienceFor(CaseModel $case, $recipients)
    {
        $involved = $case->involvedDepartmentIds();

        return $recipients->filter(fn (User $user): bool => $user->isSuperAdmin()
            || $user->departments->pluck('id')->intersect($involved)->isNotEmpty());
    }

    /**
     * All rows land on the same second otherwise, which makes the list look
     * synthetic and hides the "unread first" ordering.
     */
    private function staggerTimestamps(): void
    {
        DB::table('notifications')->orderBy('id')->get(['id'])->each(
            function (object $row, int $index): void {
                $at = Carbon::now()->subMinutes($index * 47);

                DB::table('notifications')
                    ->where('id', $row->id)
                    ->update(['created_at' => $at, 'updated_at' => $at]);
            }
        );
    }

    /**
     * Leave the newest handful per user unread so the badge shows a count.
     */
    private function markOlderAsRead(): void
    {
        User::query()->each(function (User $user): void {
            $keepUnread = $user->notifications()
                ->latest('created_at')
                ->take(random_int(2, 5))
                ->pluck('id');

            $user->notifications()
                ->whereNotIn('id', $keepUnread)
                ->update(['read_at' => Carbon::now()->subHours(random_int(1, 30))]);
        });
    }
}
