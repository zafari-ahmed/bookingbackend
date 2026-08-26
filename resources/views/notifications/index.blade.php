@php
    use App\Notifications\NotificationPayload;

    $tabLabels = [
        'all' => 'All',
        'unread' => 'Unread',
        'assignments' => 'Assignments',
        'comments' => 'Comments',
        'escalations' => 'Escalations',
    ];
@endphp

<x-layouts::app title="Notifications">
    <x-slot:actions>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn-secondary">Mark all read</button>
            </form>
        @endif
    </x-slot:actions>

    <div class="grid gap-4">
        {{-- Each tab is a query constraint, not a client-side filter. --}}
        <nav aria-label="Notification filters" class="card overflow-x-auto px-2 py-2">
            <ul class="flex min-w-max items-center gap-1">
                @foreach ($tabs as $value)
                    @php $isActive = $tab === $value; @endphp
                    <li>
                        <a
                            href="{{ route('notifications.index', $value === 'all' ? [] : ['tab' => $value]) }}"
                            @class([
                                'flex min-h-11 items-center gap-2 rounded-control px-4 text-[13.5px] font-bold transition',
                                'bg-navy text-text-inverse' => $isActive,
                                'text-text-secondary hover:bg-cream' => ! $isActive,
                            ])
                            @if ($isActive) aria-current="page" @endif
                        >
                            {{ $tabLabels[$value] }}

                            @if ($value === 'unread' && $unreadCount > 0)
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[11px] font-extrabold',
                                    'bg-text-inverse/20 text-text-inverse' => $isActive,
                                    'bg-overdue-bg text-overdue-text' => ! $isActive,
                                ])>{{ $unreadCount }}</span>
                            @elseif ($value === 'all' && $totalCount > 0)
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[11px] font-extrabold',
                                    'bg-text-inverse/20 text-text-inverse' => $isActive,
                                    'bg-cream text-text-muted' => ! $isActive,
                                ])>{{ $totalCount }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="card overflow-hidden">
            <ul class="divide-y divide-border">
                @forelse ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $badge = NotificationPayload::badgeFor($data['type'] ?? '');
                        $isUnread = $notification->read_at === null;
                    @endphp

                    <li @class([
                        'relative transition',
                        // Unread rows carry a pale-navy left accent (design-system.md page 7).
                        'border-l-[3px] border-l-navy bg-navy/[0.035]' => $isUnread,
                        'border-l-[3px] border-l-transparent' => ! $isUnread,
                    ])>
                        <a href="{{ route('notifications.open', $notification) }}"
                           class="flex items-start gap-3.5 px-4 py-4 hover:bg-cream sm:px-5">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $badge['classes'] }}"
                                  aria-hidden="true">{{ $badge['glyph'] }}</span>

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-baseline gap-x-2">
                                    <span class="text-[13.5px] font-bold text-text-primary">
                                        {{ $data['title'] ?? 'Case update' }}
                                    </span>
                                    @if (! empty($data['case_number']))
                                        <span class="font-mono text-xs text-text-muted">{{ $data['case_number'] }}</span>
                                    @endif
                                </span>

                                <span class="mt-1 block text-sm leading-relaxed text-text-secondary">
                                    @if (! empty($data['prefix']))
                                        <span class="font-semibold text-text-primary">{{ $data['prefix'] }}</span>
                                    @endif
                                    {{ $data['message'] ?? '' }}
                                </span>

                                <span class="meta mt-1.5 flex flex-wrap items-center gap-x-2">
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                                    @if (! empty($data['meta']))
                                        <span aria-hidden="true">·</span>
                                        <span>{{ $data['meta'] }}</span>
                                    @endif
                                </span>
                            </span>

                            @if ($isUnread)
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-navy" aria-label="Unread"></span>
                            @endif
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-20 text-center">
                        <p class="text-sm font-bold text-text-secondary">
                            @if ($tab === 'unread')
                                You are all caught up.
                            @else
                                Nothing here yet.
                            @endif
                        </p>
                        <p class="meta mx-auto mt-1.5 max-w-sm">
                            Assignments, remarks and escalations on your departments' cases will appear here.
                        </p>
                    </li>
                @endforelse
            </ul>

            @if ($notifications->hasPages())
                <div class="border-t border-border px-5 py-3.5">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
