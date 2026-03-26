<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Services\ConsultationClinicalNotesPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsultationPdfController extends Controller
{
    public function clinicalNotes(Request $request, Consultation $consultation, ConsultationClinicalNotesPdfService $pdfService): Response
    {
        return $pdfService->download($request, $consultation);
    }
}
