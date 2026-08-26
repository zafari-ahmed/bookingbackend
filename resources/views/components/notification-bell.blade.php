@props(['unreadCount' => 0, 'preview' => null])

@php $preview = $preview ?? collect(); @endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
    <button type="button" @click="open = ! open"
            class="relative flex size-11 items-center justify-center rounded-control border border-border bg-surface text-text-secondary transition hover:border-navy"
            :aria-expanded="open.toString()"
            aria-label="{{ $unreadCount > 0 ? $unreadCount.' unread notifications' : 'Notifications' }}">
        <x-icon name="notifications" class="size-[18px]" />

        @if ($unreadCount > 0)
            <span class="absolute -right-1.5 -top-1.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-overdue-text px-1.5 text-[10.5px] font-bold text-text-inverse">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right
         @click.outside="open = false"
         class="absolute right-0 z-40 mt-2 w-[340px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-card border border-border bg-surface shadow-[0_12px_34px_rgba(19,42,69,0.12)]">
        <div class="flex items-center justify-between border-b border-border px-4 py-3">
            <p class="text-[13px] font-bold text-navy">Notifications</p>
            <span class="meta">{{ $unreadCount }} unread</span>
        </div>

        @forelse ($preview as $notification)
            @php $data = $notification->data; @endphp
            <a href="{{ route('notifications.open', $notification) }}"
               @class([
                   'flex items-start gap-3 border-b border-border px-4 py-3 transition hover:bg-cream',
                   'bg-referred-bg/40' => $notification->read_at === null,
               ])>
                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-control text-sm font-bold {{ \App\Notifications\NotificationPayload::badgeFor($data['type'] ?? '')['classes'] }}">
                    {{ \App\Notifications\NotificationPayload::badgeFor($data['type'] ?? '')['glyph'] }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[13px] leading-snug text-text-primary">
                        {{ $data['prefix'] ?? '' }}<span class="font-bold text-navy">{{ $data['case_number'] ?? '' }}</span>{{ $data['message'] ?? '' }}
                    </span>
                    <span class="meta mt-1 block">{{ $notification->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <p class="px-4 py-8 text-center text-sm text-text-muted">No notifications yet.</p>
        @endforelse

        <a href="{{ route('notifications.index') }}"
           class="block px-4 py-3 text-center text-[13px] font-bold text-referred-text hover:text-navy">
            View all notifications
        </a>
    </div>
</div>
