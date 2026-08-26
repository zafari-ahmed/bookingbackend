<x-layouts::guest
    title="Confirm password"
    heading="Confirm your password"
    subheading="This is a secure area of the portal."
>
    <form method="POST" action="{{ route('password.confirm') }}" class="grid gap-4">
        @csrf

        <div class="grid gap-1.5">
            <label for="password" class="field-label">Password</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                   class="field @error('password') border-escalated-text @enderror">
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Confirm</button>
    </form>
</x-layouts::guest>
