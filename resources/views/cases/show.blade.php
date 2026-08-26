@php
    use App\Enums\RoutingAction;
@endphp

<x-layouts::app
    :title="$case->case_number"
    :back-link="route('dashboard')"
    back-label="Back to Dashboard"
>
    <x-slot:actions>
        {{-- Hidden on phones: the title row is already tight, and the same
             pill is repeated on the summary card a screen below. --}}
        <span class="hidden sm:inline-flex">
            <x-status-pill :status="$case->status" />
        </span>
    </x-slot:actions>

    {{--
        Desktop: summary + remarks on the left, controls on the right.
        Mobile/tablet: one column in DOM order — Priority & Status ->
        Attachments -> Activity Log -> Summary -> Remarks (design-system.md §7).
        grid-cols-1 uses minmax(0, 1fr) so native <select>s and nowrap pills
        cannot blow the page out sideways.
    --}}
    <div class="grid min-w-0 grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] lg:items-start">

        {{-- A · Priority & status control --}}
        <section class="card min-w-0 px-4 py-5 sm:px-5 lg:col-start-2 lg:row-start-1">
            <h2 class="text-base font-bold tracking-tight text-navy">Priority &amp; Status</h2>

            @can('update', $case)
                <form method="POST" action="{{ route('cases.update', $case) }}" class="mt-4 grid gap-4">
                    @csrf
                    @method('PATCH')

                    <x-priority-selector :priorities="$priorities" :selected="$case->priority" />

                    <x-form-field
                        name="status"
                        label="Status"
                        type="select"
                        :options="$statuses"
                        :value="$case->status->value"
                        required
                    />

                    <button type="submit" class="btn-primary w-full justify-center">Save changes</button>
                </form>
            @else
                <dl class="mt-4 grid gap-3">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="field-label">Priority</dt>
                        <dd><x-priority-tag :priority="$case->priority" variant="pill" /></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="field-label">Status</dt>
                        <dd><x-status-pill :status="$case->status" /></dd>
                    </div>
                </dl>
            @endcan

            @can('route', $case)
                <div class="mt-5 border-t border-border pt-5">
                    <h3 class="text-sm font-bold text-navy">Route this case</h3>

                    <form method="POST" action="{{ route('cases.routing.store', $case) }}"
                          x-data="{ action: @js(old('action', RoutingAction::Forwarded->value)) }"
                          class="mt-3 grid gap-3">
                        @csrf

                        <div class="grid gap-1.5">
                            <label for="routing-action" class="field-label">Action</label>
                            <select id="routing-action" name="action" x-model="action" class="field">
                                @foreach (RoutingAction::cases() as $routingAction)
                                    <option value="{{ $routingAction->value }}">{{ $routingAction->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-1.5" x-show="action !== @js(RoutingAction::Completed->value)">
                            <label for="routing-department" class="field-label">Department</label>
                            <select id="routing-department" name="department_id" class="field">
                                <option value="">Select a department</option>
                                @foreach ($forwardTargets as $target)
                                    <option value="{{ $target->id }}">{{ $target->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-1.5">
                            <label for="routing-notes" class="field-label">Notes</label>
                            <textarea id="routing-notes" name="notes" rows="2"
                                      placeholder="Recorded on the routing history and activity log."
                                      class="field resize-y leading-relaxed">{{ old('notes') }}</textarea>
                        </div>

                        @error('action')
                            <p class="field-error">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="btn-secondary w-full justify-center">Apply routing</button>
                    </form>
                </div>
            @endcan
        </section>

        {{-- B · Attachments --}}
        <section class="card min-w-0 px-4 py-5 sm:px-5 lg:col-start-2 lg:row-start-2">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-base font-bold tracking-tight text-navy">Attachments</h2>
                <span class="meta">{{ $case->attachments->count() }} file{{ $case->attachments->count() === 1 ? '' : 's' }}</span>
            </div>

            <div class="mt-4 grid gap-2">
                @forelse ($case->attachments as $attachment)
                    <div class="flex min-h-11 min-w-0 items-center gap-2 rounded-control border border-border bg-cream px-3 py-2">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface font-mono text-[10px] font-bold uppercase text-text-muted">
                            {{ $attachment->extensionLabel() }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-semibold text-text-primary">{{ $attachment->file_name }}</span>
                            <span class="meta block">
                                {{ $attachment->humanSize() }} · {{ $attachment->uploader?->name ?? 'Unknown' }}
                            </span>
                        </span>
                        <a href="{{ route('cases.attachments.view', [$case, $attachment]) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="flex size-11 shrink-0 items-center justify-center rounded-control text-text-muted transition hover:bg-surface hover:text-navy"
                           aria-label="View {{ $attachment->file_name }}">
                            <x-icon name="view" class="size-4" />
                        </a>
                        <a href="{{ route('cases.attachments.download', [$case, $attachment]) }}"
                           class="flex size-11 shrink-0 items-center justify-center rounded-control text-text-muted transition hover:bg-surface hover:text-navy"
                           aria-label="Download {{ $attachment->file_name }}">
                            <x-icon name="download" class="size-4" />
                        </a>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-text-muted">No files attached to this case.</p>
                @endforelse
            </div>

            @can('comment', $case)
                <form method="POST" action="{{ route('cases.attachments.store', $case) }}"
                      enctype="multipart/form-data" class="mt-4 grid gap-3 border-t border-border pt-4">
                    @csrf
                    <x-file-upload label="Add files" />
                    <button type="submit" class="btn-secondary w-full justify-center">Upload</button>
                </form>
            @endcan
        </section>

        {{-- C · Activity log --}}
        <section class="card min-w-0 px-4 py-5 sm:px-5 lg:col-start-2 lg:row-start-3">
            <h2 class="text-base font-bold tracking-tight text-navy">Activity Log</h2>
            <div class="mt-4">
                <x-activity-timeline :logs="$case->activityLogs" />
            </div>
        </section>

        {{-- D · Case summary --}}
        <section class="card min-w-0 px-4 py-5 sm:px-6 lg:col-start-1 lg:row-start-1">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-border pb-4">
                <div>
                    <p class="font-mono text-xs text-text-muted">{{ $case->case_number }}</p>
                    <h2 class="mt-1 text-xl font-extrabold tracking-tight text-navy">{{ $case->complainant_name }}</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-priority-tag :priority="$case->priority" variant="pill" />
                    <x-status-pill :status="$case->status" />
                </div>
            </div>

            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="field-label">CNIC</dt>
                    <dd class="mt-1 font-mono text-[13.5px] text-text-primary">{{ $case->complainant_cnic ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="field-label">Phone</dt>
                    <dd class="mt-1 font-mono text-[13.5px] text-text-primary">{{ $case->complainant_phone }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="field-label">Address</dt>
                    <dd class="mt-1 text-sm leading-relaxed text-text-primary">{{ $case->complainant_address ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="field-label">Issue Summary</dt>
                    <dd class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-text-primary">{{ $case->issue_summary }}</dd>
                </div>
            </dl>

            <dl class="mt-5 grid gap-4 border-t border-border pt-4 sm:grid-cols-2">
                <div>
                    <dt class="field-label">Currently With</dt>
                    <dd class="mt-1.5">
                        <span class="pill {{ $case->department->tagClasses() }}">{{ $case->department->name }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="field-label">Assigned Officer</dt>
                    <dd class="mt-1 text-sm text-text-primary">{{ $case->assignee?->name ?? 'Not yet assigned' }}</dd>
                </div>
                <div>
                    <dt class="field-label">Logged By</dt>
                    <dd class="mt-1 text-sm text-text-primary">
                        {{ $case->creator?->name ?? 'System' }}
                        <span class="meta block">{{ $case->created_at->format('d M Y, g:i A') }} · {{ $case->source->label() }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="field-label">Days Open</dt>
                    <dd class="mt-1 text-sm {{ $case->isOverdue() ? 'font-bold text-overdue-text' : 'text-text-primary' }}">
                        {{ $case->daysOpen() }} day{{ $case->daysOpen() === 1 ? '' : 's' }}
                        @if ($case->isOverdue())
                            <span class="meta block text-overdue-text">Past the {{ config('cases.overdue_after_days', 7) }} day service standard</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @if ($case->routingSteps->isNotEmpty())
                <div class="mt-5 border-t border-border pt-4">
                    <h3 class="field-label">Routing History</h3>
                    <ol class="mt-2.5 flex flex-wrap items-center gap-x-2 gap-y-2">
                        @foreach ($case->routingSteps as $step)
                            <li class="flex items-center gap-2">
                                @unless ($loop->first)
                                    <span class="text-text-muted" aria-hidden="true">&rarr;</span>
                                @endunless
                                <span class="pill max-w-[220px] truncate {{ $step->department->tagClasses() }}"
                                      title="{{ $step->action->label() }} · {{ $step->created_at->format('d M Y') }}">
                                    {{ $step->department->name }}
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </section>

        {{-- E · Remarks --}}
        <section class="card min-w-0 px-4 py-5 sm:px-6 lg:col-start-1 lg:row-start-2 lg:row-span-2">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-base font-bold tracking-tight text-navy">Remarks</h2>
                <span class="meta">{{ $case->comments->count() }} entr{{ $case->comments->count() === 1 ? 'y' : 'ies' }}</span>
            </div>

            <div class="mt-4">
                <x-comment-thread :comments="$case->comments" />
            </div>

            @can('comment', $case)
                @if ($actingDepartments->isEmpty())
                    <p class="mt-4 rounded-control border border-border bg-cream px-3.5 py-2.5 text-xs text-text-muted">
                        You are not assigned to any department currently handling this case, so you cannot record a remark.
                    </p>
                @else
                    <form method="POST" action="{{ route('cases.comments.store', $case) }}"
                          enctype="multipart/form-data" class="mt-5 grid gap-4 border-t border-border pt-5">
                        @csrf

                        <x-form-field
                            name="comment"
                            label="Add a remark"
                            type="textarea"
                            :rows="4"
                            placeholder="Record the action taken, findings or the reason for forwarding."
                            required
                        />

                        <div class="grid min-w-0 items-start gap-4 sm:grid-cols-2">
                            <x-form-field
                                name="department_id"
                                label="Recording as"
                                type="select"
                                :options="$actingDepartments->pluck('name', 'id')"
                                :value="$actingDepartments->first()->id"
                                required
                            />

                            <div class="grid gap-1.5">
                                <x-form-field
                                    name="forwarded_to_department_id"
                                    label="Forward to (optional)"
                                    type="select"
                                    placeholder-option="Do not forward"
                                    :options="$forwardTargets->pluck('name', 'id')"
                                />

                                <p class="text-xs text-text-muted">
                                    Forwarding moves the case and notifies the receiving department.
                                </p>
                            </div>
                        </div>

                        <x-file-upload label="Attach to this remark" :max="5" />

                        <button type="submit" class="btn-primary w-full justify-center sm:w-auto sm:justify-self-end">
                            Post remark
                        </button>
                    </form>
                @endif
            @endcan
        </section>
    </div>
</x-layouts::app>
