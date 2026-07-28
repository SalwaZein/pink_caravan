<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use App\Support\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private function filters(Request $request): array
    {
        return $request->only(['emirate', 'clinic_id', 'type', 'doctor_id', 'from', 'to']);
    }

    /** CSV export of the filtered records (opens in Excel). */
    public function csv(Request $request): StreamedResponse
    {
        $records = DashboardService::baseQuery($this->filters($request))
            ->with('patient', 'clinic', 'examination')->latest()->get();

        Audit::log('report.exported', null, 'Records CSV export');

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Ref', 'Patient', 'PC Number', 'Emirate', 'Clinic', 'Status', 'Result', 'Registered']);
            foreach ($records as $r) {
                fputcsv($out, [
                    $r->ref_no,
                    $r->patient?->full_name,
                    $r->patient?->pc_number,
                    $r->patient?->emirate,
                    $r->clinic?->name,
                    $r->status,
                    $r->finalResult() ?? '',
                    $r->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'pink-caravan-records-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** PDF summary of the filtered campaign metrics. */
    public function pdf(Request $request): Response
    {
        $filters = $this->filters($request);
        $stats = DashboardService::stats($filters);

        Audit::log('report.exported', null, 'Dashboard PDF export');

        $pdf = Pdf::loadView('reports.dashboard', ['stats' => $stats])->setPaper('a4');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="pink-caravan-dashboard-'.now()->format('Ymd').'.pdf"',
        ]);
    }
}
