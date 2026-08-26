<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddCommentRequest;
use App\Models\CaseModel;
use App\Models\Department;
use App\Services\AttachmentStorageService;
use App\Services\CaseWorkflowService;
use Illuminate\Http\RedirectResponse;

class CaseCommentController extends Controller
{
    public function __construct(
        private readonly CaseWorkflowService $workflow,
        private readonly AttachmentStorageService $attachments,
    ) {}

    public function store(AddCommentRequest $request, CaseModel $case): RedirectResponse
    {
        $user = $request->user();

        $comment = $this->workflow->addComment(
            case: $case,
            author: $user,
            actingAs: Department::findOrFail($request->integer('department_id')),
            body: $request->string('comment')->toString(),
            forwardTo: $request->filled('forwarded_to_department_id')
                ? Department::findOrFail($request->integer('forwarded_to_department_id'))
                : null,
        );

        if ($request->hasFile('attachments')) {
            $this->attachments->storeMany($case, $user, $request->file('attachments'), $comment);
        }

        return back()->with('status', 'Remark recorded on the case file.');
    }
}
