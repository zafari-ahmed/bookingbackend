<x-layouts::guest
    title="Choose a new password"
    heading="Choose a new password"
    subheading="Pick something you have not used on this portal before."
>
    <form method="POST" action="{{ route('password.update') }}" class="grid gap-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="grid gap-1.5">
            <label for="email" class="field-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                   required autofocus autocomplete="username"
                   class="field @error('email') border-escalated-text @enderror">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-1.5">
            <label for="password" class="field-label">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="field @error('password') border-escalated-text @enderror">
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-1.5">
            <label for="password_confirmation" class="field-label">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   required autocomplete="new-password" class="field">
        </div>

        <button type="submit" class="btn-primary w-full">Reset password</button>
    </form>
</x-layouts::guest>
