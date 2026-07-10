<?php

namespace App\Http\Controllers;

use App\Enums\MimoReport;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    private string $page = 'pages.admin-panel.reports';

    public function index(Request $request, ReportService $reportService, string $mimo_report)
    {
        $reportType = MimoReport::tryFrom($mimo_report);

        if (!$reportType) {
            abort(404, 'Invalid report type');
        }

        $report = $reportService->getReport($request, $mimo_report);

        $filters = [
            'start_date' => $request->query('start_date', now()->format('Y-m-d')),
            'end_date'   => $request->query('end_date', now()->format('Y-m-d')),
            'guardian'   => $request->query('guardian', ''),
        ];

        return view($this->page, [
            'report'     => $report,
            'reportType' => $reportType,
            'filters'    => $filters,
        ]);
    }

    public function export(Request $request, ReportService $reportService, string $mimo_report, string $format)
    {
        $reportType = MimoReport::tryFrom($mimo_report);
        if (!$reportType) {
            abort(404, 'Invalid report type');
        }

        $report = $reportService->generateReport($request, $mimo_report);

        if ($format === 'pdf') {
            return $this->exportPdf($report, $reportType);
        }

        if ($format === 'csv') {
            return $this->exportCsv($report, $reportType);
        }

        abort(415, 'Unsupported export format. Use pdf or csv.');
    }

    private function exportPdf(array $report, MimoReport $reportType)
    {
        $pdf = app('dompdf.wrapper');
        $html = view('exports.report-pdf', compact('report', 'reportType'));
        $pdf->loadHTML($html->render());

        $filename = strtolower(str_replace(' ', '_', $reportType->label()))
            . '_' . now()->format('Ymd')
            . '.pdf';

        return $pdf->download($filename);
    }

    private function exportCsv(array $report, MimoReport $reportType)
    {
        $filename = strtolower(str_replace([' ', '&', '/'], ['_', 'and', '_'], $reportType->label()))
            . '_' . now()->format('Ymd')
            . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($report) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Report: ' . $report['report_type']]);
            fputcsv($handle, ['Date Range: ' . $report['start_date'] . ' to ' . $report['end_date']]);
            fputcsv($handle, ['Generated: ' . $report['generated_at']]);
            fputcsv($handle, []);

            $data = $report['data'];
            if (!empty($data)) {
                $headers = array_keys((array) $data[0]);
                fputcsv($handle, $headers);
                foreach ($data as $row) {
                    fputcsv($handle, array_values((array) $row));
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
