<?php

namespace App\Jobs;

use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Services\TranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TranscribeConsultationRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Consultation $consultation,
        public string $storagePath
    ) {}

    public function handle(TranscriptionService $transcriptionService): void
    {
        $fullPath = Storage::disk('local')->path($this->storagePath);

        $text = $transcriptionService->transcribe($fullPath);

        if ($text === null || $text === '') {
            return;
        }

        $this->consultation->messages()->create([
            'user_id' => $this->consultation->patient_id,
            'sender' => 'patient',
            'text' => "[Video consultation transcript]\n\n" . $text,
            'attachment_url' => null,
            'source' => 'transcript',
        ]);

        Storage::disk('local')->delete($this->storagePath);
    }
}
