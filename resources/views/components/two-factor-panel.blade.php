@props([
    'enabled' => false,
    'confirmed' => false,
    'qrCodeSvg' => null,
    'recoveryCodes' => [],
    'canDisable' => true,
])

<div>
    @if (! $enabled)
        <p class="text-sm leading-relaxed text-text-secondary">
            An authenticator app adds a six-digit code after your password. Optional for
            ordinary officers; mandatory for administrative roles.
        </p>

        <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-5">
            @csrf
            <button type="submit" class="btn-primary w-full justify-center sm:w-auto">
                Enable two-factor authentication
            </button>
        </form>
    @elseif (! $confirmed)
        <div class="grid gap-5 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-start">
            <div class="rounded-card border border-border bg-surface p-3 [&_svg]:size-40">
                {!! $qrCodeSvg !!}
            </div>

            <div>
                <p class="text-sm leading-relaxed text-text-secondary">
                    Scan this code with Google Authenticator or Microsoft Authenticator, then enter
                    the six-digit code it shows to finish enrolment.
                </p>

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-4 grid gap-3">
                    @csrf

                    <div class="grid gap-1.5">
                        <label for="two-factor-code" class="field-label">Authentication code</label>
                        <input id="two-factor-code" type="text" name="code" inputmode="numeric"
                               autocomplete="one-time-code" placeholder="000000"
                               class="field max-w-[220px] font-mono text-lg tracking-[0.3em] @error('confirmTwoFactorAuthentication.code') border-escalated-text @enderror">
                        @error('confirmTwoFactorAuthentication.code')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center sm:w-auto sm:justify-self-start">
                        Confirm and continue
                    </button>
                </form>
            </div>
        </div>
    @else
        <p class="inline-flex items-center gap-2 rounded-control border border-resolved-text/25 bg-resolved-bg px-3.5 py-2.5 text-sm font-semibold text-resolved-text">
            <x-icon name="check" class="size-4" />
            Two-factor authentication is active on this account.
        </p>

        @if ($recoveryCodes !== [])
            <div class="mt-5">
                <h3 class="field-label">Recovery Codes</h3>
                <p class="meta mt-1">
                    Store these somewhere safe. Each one signs you in once if you lose your device.
                </p>

                <ul class="mt-3 grid gap-1.5 rounded-card border border-border bg-cream px-4 py-3.5 font-mono text-[13px] text-text-primary sm:grid-cols-2">
                    @foreach ($recoveryCodes as $recoveryCode)
                        <li>{{ $recoveryCode }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <form method="POST" action="{{ route('two-factor.recovery-codes') }}">
                @csrf
                <button type="submit" class="btn-secondary w-full justify-center sm:w-auto">
                    Regenerate recovery codes
                </button>
            </form>

            @if ($canDisable)
                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary w-full justify-center sm:w-auto">
                        Disable two-factor authentication
                    </button>
                </form>
            @else
                <p class="flex items-center text-sm text-text-muted">
                    Your role must keep two-factor authentication enabled.
                </p>
            @endif
        </div>
    @endif
</div>
