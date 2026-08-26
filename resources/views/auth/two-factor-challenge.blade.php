<x-layouts::guest
    title="Two-factor authentication"
    heading="Two-factor authentication"
    subheading="Enter the code from your authenticator app."
>
    <div x-data="{ recovery: false }">
        <form method="POST" action="{{ route('two-factor.login') }}" class="grid gap-4">
            @csrf

            <div x-show="! recovery" class="grid gap-1.5">
                <label for="code" class="field-label">Authentication code</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                       x-ref="code" placeholder="000000"
                       class="field text-center font-mono text-lg tracking-[0.4em] @error('code') border-escalated-text @enderror">
                @error('code')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="recovery" x-cloak class="grid gap-1.5">
                <label for="recovery_code" class="field-label">Recovery code</label>
                <input id="recovery_code" type="text" name="recovery_code" autocomplete="one-time-code"
                       class="field font-mono @error('recovery_code') border-escalated-text @enderror">
                @error('recovery_code')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full">Verify</button>

            <button type="button" @click="recovery = ! recovery"
                    class="text-center text-xs font-semibold text-referred-text hover:text-navy"
                    x-text="recovery ? 'Use an authentication code instead' : 'Use a recovery code instead'">
            </button>
        </form>
    </div>
</x-layouts::guest>
