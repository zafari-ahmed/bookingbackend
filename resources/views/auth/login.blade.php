<x-layouts::guest title="Sign in">
    @if (session('status'))
        <p class="mb-4 rounded-control border border-resolved-text/25 bg-resolved-bg px-3.5 py-2.5 text-xs leading-relaxed text-resolved-text">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ route('login') }}" class="grid gap-4">
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

        <div x-data="{ show: false }" class="grid gap-1.5">
            <label for="password" class="field-label">Password</label>
            <div class="flex items-center gap-1 rounded-control border border-border bg-surface pl-3.5 pr-1.5 focus-within:border-teal @error('password') border-escalated-text @enderror">
                <input id="password" :type="show ? 'text' : 'password'" name="password"
                       required autocomplete="current-password"
                       placeholder="Enter your password"
                       class="min-h-11 w-full min-w-0 border-0 bg-transparent text-sm text-text-primary outline-none placeholder:text-text-muted">
                <button type="button" @click="show = ! show"
                        class="shrink-0 rounded-lg px-2.5 py-2 text-xs font-bold text-text-secondary hover:bg-cream"
                        x-text="show ? 'Hide' : 'Show'"
                        :aria-label="show ? 'Hide password' : 'Show password'">Show</button>
            </div>
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-xs text-text-secondary">
                    <input type="checkbox" name="remember" class="size-4 rounded border-border text-teal focus:ring-teal">
                    Remember this device
                </label>

                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-referred-text hover:text-navy">
                    Forgot password?
                </a>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full">Sign In</button>
    </form>
</x-layouts::guest>
