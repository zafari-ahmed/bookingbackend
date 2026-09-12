<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Sport;
use App\Services\ReportService;
use App\Support\SimpleXlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $this->reports->filtersFromRequest($request->all());
        $summary = $this->reports->summary($filters);
        $charts = $this->reports->charts($filters);
        $bookings = $this->reports->query($filters)->paginate(20)->withQueryString();

        return view('reports.index', [
            'filters' => $filters,
            'summary' => $summary,
            'money' => $this->reports->moneySummary($summary),
            'charts' => $charts,
            'bookings' => $bookings,
            'sports' => Sport::query()->orderBy('name')->get(),
            'courts' => Court::query()->with('sport')->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->reports->filtersFromRequest($request->all());
        $rows = $this->reports->exportRows($filters);
        $headers = $this->reports->headers();
        $name = 'sport-avenue-bookings-'.$filters['from'].'-'.$filters['to'];

        return match ($format) {
            'csv' => $this->csv($name, $headers, $rows),
            'xlsx', 'excel' => SimpleXlsx::download($name.'.xlsx', $headers, $rows),
            'pdf' => $this->pdf($name, $filters, $headers, $rows),
            default => abort(404),
        };
    }

    private function csv(string $name, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $name.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function pdf(string $name, array $filters, array $headers, array $rows)
    {
        set_time_limit(180);
        $summary = $this->reports->summary($filters);
        $pdf = Pdf::setOptions([
            'isHtml5ParserEnabled' => false,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ])->loadView('reports.pdf', [
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $filters,
            'summary' => $summary,
            'money' => $this->reports->moneySummary($summary),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($name.'.pdf');
    }
}
