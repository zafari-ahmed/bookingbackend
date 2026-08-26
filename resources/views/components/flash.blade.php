@php
    $status = session('status');
    $statusMessages = [
        'two-factor-authentication-enabled' => 'Scan the QR code with your authenticator app to finish enrolment.',
        'two-factor-authentication-confirmed' => 'Two-factor authentication is now active on this account.',
        'two-factor-authentication-disabled' => 'Two-factor authentication has been turned off.',
        'recovery-codes-generated' => 'New recovery codes have been generated. Store them somewhere safe.',
        'password-updated' => 'Your password has been updated.',
        'profile-information-updated' => 'Your profile has been updated.',
    ];
    $statusMessage = is_string($status) ? ($statusMessages[$status] ?? $status) : null;
@endphp

@if ($statusMessage)
    <div class="mb-5 flex items-start gap-3 rounded-card border border-resolved-text/25 bg-resolved-bg px-4 py-3.5" role="status">
        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-resolved-text text-text-inverse">
            <x-icon name="check" class="size-3" />
        </span>
        <p class="text-sm leading-relaxed text-resolved-text">{{ $statusMessage }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-card border border-escalated-text/25 bg-escalated-bg px-4 py-3.5" role="alert">
        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-escalated-text text-text-inverse">
            <x-icon name="info" class="size-3" />
        </span>
        <div class="text-sm leading-relaxed text-escalated-text">
            <p class="font-bold">{{ trans_choice('Please correct the following issue|Please correct the following issues', $errors->count()) }}:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
