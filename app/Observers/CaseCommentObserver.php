<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ActivityType;
use App\Models\CaseActivityLog;
use App\Models\CaseComment;
use Illuminate\Support\Str;

class CaseCommentObserver
{
    public function created(CaseComment $comment): void
    {
        $comment->loadMissing(['user', 'department', 'forwardedToDepartment']);

        $description = sprintf(
            'Remark added by %s (%s).',
            $comment->user?->name ?? 'a removed user',
            $comment->department?->name ?? 'unknown department',
        );

        if ($comment->forwardedToDepartment !== null) {
            $description .= ' Forwarded to '.$comment->forwardedToDepartment->name.'.';
        }

        CaseActivityLog::create([
            'case_id' => $comment->case_id,
            'user_id' => $comment->user_id,
            'action_type' => ActivityType::CommentAdded,
            'description' => $description,
            'meta' => [
                'comment_id' => $comment->getKey(),
                'excerpt' => Str::limit($comment->comment, 140),
            ],
        ]);
    }
}
