<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\UserRole;
use App\Models\CaseActivityLog;
use App\Models\CaseComment;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use Database\Factories\CaseCommentFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CaseCommentSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Collection<int, User> $officers */
        $officers = User::with('departments')->get();

        /** @var User $acOfficer */
        $acOfficer = $officers->firstWhere('role', UserRole::SuperAdmin);

        $acDepartment = Department::query()->orderBy('id')->firstOrFail();

        CaseModel::with('routingSteps')->each(function (CaseModel $case) use ($officers, $acOfficer, $acDepartment): void {
            $involvedIds = $case->involvedDepartmentIds();
            $timestamp = $case->created_at->copy()->addHours(random_int(2, 20));

            // The AC office always records the intake remark first.
            $this->addComment(
                $case,
                $acOfficer,
                $acDepartment,
                'Application received at the front desk and entered in the district register. Verified the complainant\'s particulars before referral.',
                $timestamp,
            );

            // Then two to four officers from the departments that actually hold
            // the case weigh in, so the thread shows cross-department traffic.
            $participants = $officers
                ->filter(fn (User $user): bool => $user->departments
                    ->pluck('id')
                    ->intersect($involvedIds)
                    ->isNotEmpty())
                ->shuffle()
                ->take(random_int(1, 4));

            foreach ($participants as $officer) {
                $department = $officer->departments
                    ->whereIn('id', $involvedIds)
                    ->first() ?? $officer->departments->first();

                if ($department === null) {
                    continue;
                }

                $timestamp = $timestamp->copy()->addHours(random_int(6, 60));

                if ($timestamp->isFuture()) {
                    break;
                }

                $this->addComment(
                    $case,
                    $officer,
                    $department,
                    CaseCommentFactory::REMARKS[array_rand(CaseCommentFactory::REMARKS)],
                    $timestamp,
                );
            }
        });
    }

    private function addComment(
        CaseModel $case,
        User $author,
        Department $department,
        string $body,
        Carbon $at,
    ): void {
        $comment = CaseComment::create([
            'case_id' => $case->getKey(),
            'user_id' => $author->getKey(),
            'department_id' => $department->getKey(),
            'comment' => $body,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        // The observer already logged the remark against `now()`; realign it
        // with the backdated comment so the timeline stays in order.
        CaseActivityLog::where('case_id', $case->getKey())
            ->where('action_type', ActivityType::CommentAdded)
            ->whereJsonContains('meta->comment_id', $comment->getKey())
            ->update(['created_at' => $at]);
    }
}
