<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConsultationSummaryExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConsultationSummaryExportController extends Controller
{
    public function export(Request $request, string $format, ConsultationSummaryExportService $exportService): Response|\Symfony\Component\HttpFoundation\Response
    {
        $validated = $request->validate([
            'patient_id' => ['nullable', 'integer', 'exists:users,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:scheduled,completed,cancelled'],
            'consultation_type' => ['nullable', 'in:text,audio,video'],
        ]);
        abort_unless(in_array($format, ['json', 'excel', 'pdf'], true), 404);

        $rows = $exportService->rows($validated);
        $columns = $exportService->columns();
        $date = now()->format('Y-m-d_His');

        if ($format === 'json') {
            $payload = json_encode([
                'generated_at' => now()->toISOString(),
                'columns' => $columns,
                'count' => $rows->count(),
                'data' => $rows->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return response($payload, 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => "attachment; filename=clinical-summaries-{$date}.json",
            ]);
        }

        if ($format === 'excel') {
            $html = view('exports.consultation-clinical-summaries-excel', [
                'columns' => $columns,
                'rows' => $rows,
            ])->render();

            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
                'Content-Disposition' => "attachment; filename=clinical-summaries-{$date}.xls",
            ]);
        }

        $pdf = Pdf::loadView('exports.consultation-clinical-summaries-pdf', [
            'columns' => $columns,
            'rows' => $rows,
            'generatedAt' => now()->format('F j, Y g:i A'),
        ])->setPaper('a3', 'landscape');

        return $pdf->download("clinical-summaries-{$date}.pdf");
    }
}
