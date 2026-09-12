<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p { margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 2px 3px; }
        th { background: #0b1d36; color: #fff; }
    </style>
</head>
<body>
    <h1>Sport Avenue Club — Booking Report</h1>
    <p>{{ $filters['from'] }} to {{ $filters['to'] }} · Total {{ $summary['total_bookings'] }} · Collected {{ $money['collected'] }} · Outstanding {{ $money['outstanding'] }}</p>
    <table>
        <thead>
            <tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
