<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Sign in' }} · {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- The one screen without a sidebar: full-height cream, single centred card. --}}
<body class="min-h-screen bg-cream font-sans text-text-primary">
    <div class="flex min-h-screen items-center justify-center px-5 py-8">
        <div class="grid w-full max-w-[420px] gap-4">
            <div class="card px-6 py-9 shadow-[0_1px_2px_rgba(19,42,69,0.04),0_8px_24px_rgba(19,42,69,0.06)] sm:px-8">
                <div class="flex justify-center">
                    <span class="flex size-13 items-center justify-center rounded-full bg-teal text-[17px] font-extrabold tracking-wider text-text-inverse">
                        DC
                    </span>
                </div>

                <div class="mt-5 text-center">
                    <p class="text-[10.5px] font-semibold uppercase tracking-[0.16em] text-text-muted">
                        Government of Sindh
                    </p>
                    <h1 class="mt-2 text-xl font-extrabold leading-tight tracking-tight text-navy">
                        {{ $heading ?? 'District Coordination' }}<br/>Case Management System
                    </h1>
                    <p class="mt-2 text-sm text-text-secondary">{{ $subheading ?? 'Sign in to continue' }}</p>
                </div>

                <div class="mt-7">
                    {{ $slot }}
                </div>

                <div class="mt-6 border-t border-border pt-4">
                    <p class="text-center text-xs leading-relaxed text-text-muted">
                        For access issues, contact your AC office administrator.
                    </p>
                </div>
            </div>

            <p class="text-center text-[11.5px] leading-relaxed text-text-muted">
                Official use only · Office of the Assistant Commissioner
            </p>
        </div>
    </div>
</body>
</html>
