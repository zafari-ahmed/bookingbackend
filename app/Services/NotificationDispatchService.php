<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\CaseAttachment;
use App\Models\CaseComment;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AttachmentAddedNotification;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\CaseEscalatedNotification;
use App\Notifications\CaseResolvedNotification;
use App\Notifications\CommentAddedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Chooses who hears about a case event and writes the in-app (database) row
 * in the same request so the bell updates without a queue worker.
 *
 * Recipients:
 *   - super_admin — every event on every case
 *   - officers in a department that holds the case, or has held it
 *   - the officer the case is personally assigned to
 *   - never the person who just took the action
 */
class NotificationDispatchService
{
    /**
     * How long the bell icon may serve a stale unread count. Dashboard-grade
     * freshness is enough; a COUNT on every page load is not.
     */
    public const UNREAD_COUNT_TTL_SECONDS = 60;

    public function caseAssigned(CaseModel $case, Department $department, User $actor): void
    {
        $this->send(
            $this->stakeholders($case, $actor),
            new CaseAssignedNotification($case, $department),
        );
    }

    public function caseCommented(CaseComment $comment, User $actor): void
    {
        $comment->loadMissing('case');

        $this->send(
            $this->stakeholders($comment->case, $actor),
            new CommentAddedNotification($comment),
        );
    }

    /**
     * @param  array<int, CaseAttachment>  $attachments
     */
    public function attachmentsAdded(CaseModel $case, User $actor, array $attachments): void
    {
        $fileNames = array_values(array_filter(array_map(
            fn (CaseAttachment $attachment): string => $attachment->file_name,
            $attachments,
        )));

        if ($fileNames === []) {
            return;
        }

        $this->send(
            $this->stakeholders($case, $actor),
            new AttachmentAddedNotification($case, $fileNames, $actor->name),
        );
    }

    public function caseEscalated(CaseModel $case, User $actor): void
    {
        $this->send(
            $this->stakeholders($case, $actor),
            new CaseEscalatedNotification($case),
        );
    }

    public function caseResolved(CaseModel $case, User $actor): void
    {
        $this->send(
            $this->stakeholders($case, $actor),
            new CaseResolvedNotification($case),
        );
    }

    /**
     * Cached unread badge count for the top bar.
     */
    public function unreadCountFor(User $user): int
    {
        return Cache::remember(
            self::unreadCountCacheKey($user),
            self::UNREAD_COUNT_TTL_SECONDS,
            fn (): int => $user->unreadNotifications()->count(),
        );
    }

    public function forgetUnreadCount(User $user): void
    {
        Cache::forget(self::unreadCountCacheKey($user));
    }

    public static function unreadCountCacheKey(User $user): string
    {
        return 'notifications:unread-count:'.$user->getKey();
    }

    /**
     * Super admins, every officer whose department is on the case, and the
     * assigned officer — minus whoever triggered the event.
     *
     * @return Collection<int, User>
     */
    private function stakeholders(CaseModel $case, ?User $except = null): Collection
    {
        $departmentIds = $case->involvedDepartmentIds();

        return User::query()
            ->active()
            ->where(function (Builder $query) use ($case, $departmentIds): void {
                $query->where('role', UserRole::SuperAdmin);

                if ($departmentIds !== []) {
                    $query->orWhereHas(
                        'departments',
                        fn ($departments) => $departments->whereIn('departments.id', $departmentIds),
                    );
                }

                if ($case->assigned_to_user_id) {
                    $query->orWhereKey($case->assigned_to_user_id);
                }
            })
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->get();
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function send(Collection $recipients, Notification $notification): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        NotificationFacade::send($recipients, $notification);
        $this->flushUnreadCounts($recipients);
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function flushUnreadCounts(Collection $recipients): void
    {
        $recipients->each(fn (User $user) => $this->forgetUnreadCount($user));
    }
}
