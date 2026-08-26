@props(['name', 'class' => 'size-5'])

@php
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="10" width="7" height="11" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
        'cases' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 4v16"/>',
        'new-case' => '<path d="M12 5v14M5 12h14"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'priority' => '<path d="M12 4l9 16H3l9-16Z"/><path d="M12 10v4M12 17h.01"/>',
        'departments' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/>',
        'notifications' => '<path d="M18 8a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7Z"/><path d="M10.3 20a2 2 0 0 0 3.4 0"/>',
        'reports' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 15v-4M12 15V8M16 15v-6"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 9 19.4a1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1A1.6 1.6 0 0 0 4.6 9a1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3H9a1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9a1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1Z"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'download' => '<path d="M12 3v12M7 11l5 5 5-5"/><path d="M4 20h16"/>',
        'view' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'paperclip' => '<path d="M21 12.5 12.5 21a5 5 0 0 1-7-7l8.5-8.5a3.5 3.5 0 0 1 5 5L10.5 19a2 2 0 0 1-3-3l8-8"/>',
        'upload' => '<path d="M12 20V8M7 12l5-5 5 5"/><path d="M4 20h16" opacity=".35"/>',
        'check' => '<path d="m5 13 4 4L19 7"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'forward' => '<path d="M4 12h15M14 7l5 5-5 5"/>',
        'user' => '<path d="M20 21v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1"/><circle cx="12" cy="8" r="3.5"/>',
        'users' => '<path d="M16 21v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1"/><circle cx="9" cy="8" r="3.5"/><path d="M22 21v-1a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'activity' => '<path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/>',
        'user-plus' => '<path d="M15 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"/><circle cx="9" cy="8" r="3.5"/><path d="M18 8v6M21 11h-6"/>',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01M10 21v-4h4v4"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {!! $paths[$name] ?? $paths['info'] !!}
</svg>
