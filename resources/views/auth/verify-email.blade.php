<x-layouts::guest
    title="Verify your email"
    heading="Verify your email address"
    subheading="Confirm the address on file before opening the case register."
>
    @if (session('status') === 'verification-link-sent')
        <p class="mb-4 rounded-control border border-resolved-text/25 bg-resolved-bg px-3.5 py-2.5 text-xs leading-relaxed text-resolved-text">
            A fresh verification link has been sent to your email address.
        </p>
    @endif

    <p class="mb-4 text-sm leading-relaxed text-text-secondary">
        We sent a signed verification link to your registered address. Open it to activate your account.
    </p>

    <div class="grid gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-secondary w-full">Sign out</button>
        </form>
    </div>
</x-layouts::guest>
