{{ $headline }}

{{ $intro }}

{{ $placeLine }}
{{ \Illuminate\Support\Str::limit($case->issue_summary, 180) }}
{{ $metaLine }}

Open the case: {{ $actionUrl }}

Automated alert · {{ $officeName }}
Manage notification settings: {{ $settingsUrl }}
