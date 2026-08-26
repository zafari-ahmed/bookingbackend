<x-layouts::guest
    title="Reset password"
    heading="Reset your password"
    subheading="We'll email you a signed link that expires shortly."
>
    @if (session('status'))
        <p class="mb-4 rounded-control border border-resolved-text/25 bg-resolved-bg px-3.5 py-2.5 text-xs leading-relaxed text-resolved-text">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="grid gap-4">
        @csrf

        <div class="grid gap-1.5">
            <label for="email" class="field-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   placeholder="name@sindh.gov.pk"
                   class="field @error('email') border-escalated-text @enderror">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Email password reset link</button>

        <a href="{{ route('login') }}" class="text-center text-xs font-semibold text-referred-text hover:text-navy">
            Back to sign in
        </a>
    </form>
</x-layouts::guest>
