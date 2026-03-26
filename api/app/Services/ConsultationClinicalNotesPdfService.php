<?php

namespace App\Services;

use App\Models\Consultation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsultationClinicalNotesPdfService
{
    public function download(Request $request, Consultation $consultation): Response
    {
        $consultation->loadMissing(['patient', 'doctor', 'messages']);

        $notes = is_array($consultation->clinical_notes) ? $consultation->clinical_notes : [];
        $uploads = $consultation->metadata['patient_investigation_uploads'] ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        $hasNotes = $this->clinicalNotesHasLeafContent($notes);
        $hasMessages = $consultation->messages->isNotEmpty();
        $hasUploads = count($uploads) > 0;

        if (! $hasNotes && ! $hasMessages && ! $hasUploads) {
            abort(404, 'No clinical record, uploads, or conversation to export.');
        }

        $pdf = Pdf::loadView('consultation-clinical-notes-full', [
            'consultation' => $consultation,
            'notes' => $notes,
            'uploads' => $uploads,
            'messages' => $consultation->messages,
            'request' => $request,
        ])->setPaper('a4');

        return $pdf->download('consultation-'.$consultation->id.'-clinical-record.pdf');
    }

    /**
     * True if any non-empty string (or meaningful leaf) exists in nested clinical_notes.
     */
    private function clinicalNotesHasLeafContent(array $notes): bool
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($notes));
        foreach ($it as $leaf) {
            if (is_string($leaf) && trim($leaf) !== '') {
                return true;
            }
        }

        return false;
    }
}
