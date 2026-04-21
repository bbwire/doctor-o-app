<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use App\Services\AuditLogService;
use App\Services\ConsultationClinicalNotesPdfService;
use App\Services\EntityNumberGenerator;
use App\Services\NotificationService;
use App\Services\SettingsService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ConsultationController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['scheduled', 'waiting', 'completed', 'cancelled'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $doctorId = $request->user()->id;

        $perPage = min((int) $request->get('per_page', 15), 100);

        $query = Consultation::query()
            ->with('patient')
            ->where('doctor_id', $doctorId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), function ($q) use ($request) {
                $from = Carbon::parse($request->string('from'))->toDateTimeString();
                $q->where('scheduled_at', '>=', $from);
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $to = Carbon::parse($request->string('to'))->toDateTimeString();
                $q->where('scheduled_at', '<=', $to);
            })
            ->orderBy('scheduled_at');

        $consultations = $query->paginate($perPage);

        return ConsultationResource::collection($consultations);
    }

    public function show(Request $request, Consultation $consultation)
    {
        abort_unless(
            (int) $consultation->doctor_id === (int) $request->user()->id,
            403,
            'You do not have access to this consultation.'
        );

        $consultation->load(['patient', 'prescriptions', 'messages']);

        return new ConsultationResource($consultation);
    }

    public function downloadClinicalNotesPdf(Request $request, Consultation $consultation, ConsultationClinicalNotesPdfService $pdfService): Response
    {
        abort_unless(
            (int) $consultation->doctor_id === (int) $request->user()->id,
            403,
            'You do not have access to this consultation.'
        );

        return $pdfService->download($request, $consultation);
    }

    public function update(Request $request, Consultation $consultation): JsonResponse
    {
        abort_unless(
            (int) $consultation->doctor_id === (int) $request->user()->id,
            403,
            'You do not have access to this consultation.'
        );

        $clinicalNotesInput = $request->input('clinical_notes');
        if (is_array($clinicalNotesInput) && array_key_exists('presenting_complaints', $clinicalNotesInput)
            && is_array($clinicalNotesInput['presenting_complaints'])) {
            $clinicalNotesInput['presenting_complaints'] = self::normalizePresentingComplaintsPayload(
                $clinicalNotesInput['presenting_complaints']
            );
            $request->merge(['clinical_notes' => $clinicalNotesInput]);
        }

        if (is_array($clinicalNotesInput) && array_key_exists('review_of_systems', $clinicalNotesInput)) {
            $clinicalNotesInput['review_of_systems'] = self::normalizeReviewOfSystemsPayload(
                $clinicalNotesInput['review_of_systems']
            );
            $request->merge(['clinical_notes' => $clinicalNotesInput]);
        }

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:65535'],
            'metadata' => ['nullable', 'array'],
            'clinical_notes' => ['nullable', 'array'],
            'clinical_notes.presenting_complaint' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.presenting_complaints' => ['nullable', 'array'],
            'clinical_notes.presenting_complaints.*.complaint' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.presenting_complaints.*.duration' => ['nullable', 'string', 'max:512'],
            'clinical_notes.history_of_presenting_complaint' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.review_of_systems' => ['nullable', 'array'],
            'clinical_notes.review_of_systems.cns' => ['nullable', 'string', 'max:16384'],
            'clinical_notes.review_of_systems.respiratory' => ['nullable', 'string', 'max:16384'],
            'clinical_notes.review_of_systems.cardiovascular' => ['nullable', 'string', 'max:16384'],
            'clinical_notes.review_of_systems.digestive' => ['nullable', 'string', 'max:16384'],
            'clinical_notes.review_of_systems.genitourinary' => ['nullable', 'string', 'max:16384'],
            'clinical_notes.review_of_systems.locomotor' => ['nullable', 'string', 'max:16384'],
            'clinical_notes.review_of_systems.other' => ['nullable', 'string', 'max:16384'],
            'clinical_notes.past_medical_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.past_surgical_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.growth_and_development' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.immunization_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.family_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.social_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.summary_of_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.differential_diagnosis' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.investigation_results' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.final_treatment' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan' => ['nullable', 'array'],
            'clinical_notes.management_plan.treatment' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.investigation_radiology' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.investigation_laboratory' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.investigation_interventional' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.referrals' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.in_person_visit' => ['nullable', 'array'],
            'clinical_notes.management_plan.in_person_visit.revisit_history' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.in_person_visit.general_examination' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        return;
                    }
                    if (! is_string($value) && ! is_array($value)) {
                        $fail('The '.$attribute.' must be a string or an object.');
                    }
                },
            ],
            'clinical_notes.management_plan.in_person_visit.general_examination.appearance' => ['nullable', 'string', 'in:Good,Sick,Very sick'],
            'clinical_notes.management_plan.in_person_visit.general_examination.jaundice' => ['nullable', 'string', 'in:Nil,Mild,Severe'],
            'clinical_notes.management_plan.in_person_visit.general_examination.anemia' => ['nullable', 'string', 'in:Present,Absent'],
            'clinical_notes.management_plan.in_person_visit.general_examination.cyanosis' => ['nullable', 'string', 'in:Present,Absent'],
            'clinical_notes.management_plan.in_person_visit.general_examination.clubbing' => ['nullable', 'string', 'in:Present,Absent'],
            'clinical_notes.management_plan.in_person_visit.general_examination.oedema' => ['nullable', 'string', 'in:Grade I,Grade II,Grade III,Grade IV'],
            'clinical_notes.management_plan.in_person_visit.general_examination.lymphadenopathy' => ['nullable', 'string', 'in:Nil,Present'],
            'clinical_notes.management_plan.in_person_visit.general_examination.dehydration' => ['nullable', 'string', 'in:Nil,Some,Severe'],
            'clinical_notes.management_plan.in_person_visit.system_examination' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.management_plan.selected_categories' => ['nullable', 'array'],
            'clinical_notes.management_plan.selected_categories.*' => ['string', 'in:treatment,investigation_radiology,investigation_laboratory,investigation_interventional,referrals,in_person_visit'],
            'clinical_notes.final_diagnosis_icd11' => ['nullable', 'array'],
            'clinical_notes.final_diagnosis_icd11.code' => ['nullable', 'string', 'max:64'],
            'clinical_notes.final_diagnosis_icd11.title' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.differential_diagnoses_icd11' => ['nullable', 'array'],
            'clinical_notes.differential_diagnoses_icd11.*.code' => ['nullable', 'string', 'max:64'],
            'clinical_notes.differential_diagnoses_icd11.*.title' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.final_diagnosis' => ['nullable', 'string', 'max:65535'],
            'clinical_notes.outcome' => ['nullable', 'array'],
            'clinical_notes.outcome.doctor_notes' => ['nullable', 'string', 'max:65535'],
        ]);

        if (isset($validated['clinical_notes']) && is_array($validated['clinical_notes'])) {
            $prevNotes = is_array($consultation->clinical_notes) ? $consultation->clinical_notes : [];
            $prevOutcome = is_array($prevNotes['outcome'] ?? null) ? $prevNotes['outcome'] : [];
            $incoming = $validated['clinical_notes'];
            if (! array_key_exists('outcome', $incoming)) {
                if ($prevOutcome !== []) {
                    $validated['clinical_notes']['outcome'] = $prevOutcome;
                }
            } elseif (is_array($incoming['outcome'])) {
                $incOutcome = $incoming['outcome'];
                unset($incOutcome['patient_reports_improved'], $incOutcome['patient_reported_at']);
                $validated['clinical_notes']['outcome'] = array_merge($prevOutcome, $incOutcome);
            }

            if (array_key_exists('presenting_complaints', $validated['clinical_notes'])
                && is_array($validated['clinical_notes']['presenting_complaints'])) {
                $flat = self::flattenPresentingComplaintsForLegacy($validated['clinical_notes']['presenting_complaints']);
                $validated['clinical_notes']['presenting_complaint'] = $flat === '' ? null : $flat;
            }

            if (array_key_exists('review_of_systems', $validated['clinical_notes'])
                && is_array($validated['clinical_notes']['review_of_systems'])) {
                $ros = $validated['clinical_notes']['review_of_systems'];
                $trimmed = [];
                foreach ($ros as $k => $v) {
                    if (is_string($v) && trim($v) !== '') {
                        $trimmed[$k] = trim($v);
                    }
                }
                $validated['clinical_notes']['review_of_systems'] = $trimmed === [] ? null : $trimmed;
            }
        }

        $oldStatus = $consultation->status;
        $consultation->update($validated);

        // Generate referral number when the consultation includes referral content.
        // (Referral entity isn't modeled separately yet, so we attach a single RF code to the consultation.)
        $consultation->refresh();
        if (! $consultation->referral_number) {
            $referrals = $consultation->clinical_notes['management_plan']['referrals'] ?? null;
            if (is_string($referrals) && trim($referrals) !== '') {
                $consultation->referral_number = app(EntityNumberGenerator::class)->generate('RF', $consultation->created_at);
                $consultation->saveQuietly();
                $consultation->refresh();
            }
        }

        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            $action = $validated['status'] === 'completed' ? 'consultation.completed' : ($validated['status'] === 'cancelled' ? 'consultation.cancelled_by_doctor' : 'consultation.status_updated');
            app(AuditLogService::class)->log(
                $request->user(),
                $action,
                'Doctor marked consultation #'.$consultation->id.' as '.$validated['status'],
                Consultation::class,
                $consultation->id,
                ['old_status' => $oldStatus, 'new_status' => $validated['status']]
            );
        }

        return response()->json([
            'data' => (new ConsultationResource($consultation->load(['patient', 'prescriptions'])))->toArray($request),
        ]);
    }

    /**
     * Doctor waiting room: list consultations that are waiting for assignment
     * and match the doctor's speciality category.
     */
    public function queue(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $doctor = $request->user();
        $speciality = $doctor->healthcareProfessional?->speciality;

        $perPage = min((int) $request->get('per_page', 15), 100);

        $query = Consultation::query()
            ->with(['patient'])
            ->where('status', 'waiting')
            ->whereNull('doctor_id')
            ->orderBy('scheduled_at');

        // If doctor isn't active/approved or doesn't have speciality yet, show nothing.
        if ($speciality && $doctor->healthcareProfessional?->is_active && $doctor->healthcareProfessional?->is_approved) {
            $query->where('metadata->requested_category', $speciality);
        } else {
            $query->whereRaw('1 = 0');
        }

        $consultations = $query->paginate($perPage);

        return ConsultationResource::collection($consultations);
    }

    /**
     * Claim a waiting consultation:
     * - assign the doctor_id
     * - set status to scheduled
     * - charge the patient's wallet
     */
    public function claim(Request $request, Consultation $consultation): ConsultationResource
    {
        $doctor = $request->user();
        $doctorSpeciality = $doctor->healthcareProfessional?->speciality;
        abort_unless(
            $doctor->healthcareProfessional?->is_active && $doctor->healthcareProfessional?->is_approved,
            403,
            'Your doctor profile is not active/approved.'
        );

        abort_unless(
            $consultation->status === 'waiting' && $consultation->doctor_id === null,
            422,
            'This consultation is not available for claiming.'
        );

        $requestedCategory = $consultation->metadata['requested_category'] ?? null;

        abort_unless(
            $doctorSpeciality && $requestedCategory && $requestedCategory === $doctorSpeciality,
            403,
            'This consultation is not in your speciality category.'
        );

        $scheduledAt = $consultation->scheduled_at?->toDateTimeString();
        abort_unless(is_string($scheduledAt) && $scheduledAt !== '', 422, 'Invalid consultation scheduled time.');

        return DB::transaction(function () use ($doctor, $consultation, $scheduledAt) {
            // Lock the consultation row to avoid double-claiming.
            $locked = Consultation::query()->where('id', $consultation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'waiting' || $locked->doctor_id !== null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'consultation' => ['This consultation has already been claimed.'],
                ]);
            }

            // Re-check doctor's speciality match from the latest locked state.
            $lockedRequestedCategory = $locked->metadata['requested_category'] ?? null;
            $doctorSpeciality = $doctor->healthcareProfessional?->speciality;

            if (! $doctorSpeciality || ! $lockedRequestedCategory || $lockedRequestedCategory !== $doctorSpeciality) {
                abort(403, 'This consultation is not in your speciality category.');
            }

            // Ensure the doctor is available within their working hours (if set).
            $availabilityStart = $doctor->healthcareProfessional?->availability_start_time;
            $availabilityEnd = $doctor->healthcareProfessional?->availability_end_time;
            if ($availabilityStart && $availabilityEnd) {
                $time = Carbon::parse($scheduledAt)->format('H:i:s');
                $startTime = $availabilityStart->format('H:i:s');
                $endTime = $availabilityEnd->format('H:i:s');
                abort_unless($time >= $startTime && $time <= $endTime, 422, 'Doctor is not available at that time.');
            }

            // Ensure no scheduled consultation already exists for this doctor at the same time.
            $isDoctorSlotTaken = Consultation::query()
                ->where('doctor_id', $doctor->id)
                ->where('status', 'scheduled')
                ->where('scheduled_at', $scheduledAt)
                ->exists();

            if ($isDoctorSlotTaken) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'scheduled_at' => ['This doctor is already booked at that date and time.'],
                ]);
            }

            $locked->update([
                'doctor_id' => $doctor->id,
                'status' => 'scheduled',
                'metadata' => array_merge($locked->metadata ?? [], [
                    'claimed_at' => now()->toISOString(),
                    'claimed_by' => 'doctor',
                ]),
            ]);

            $locked->refresh()->load(['patient', 'doctor.healthcareProfessional.institution']);

            // Charge patient only when doctor claims (decision B).
            $doctorProfile = $doctor->healthcareProfessional;
            $amount = $this->settingsService->getConsultationAmountForDoctor($doctorProfile);
            if ($amount > 0) {
                app(WalletService::class)->chargeForConsultation($locked->patient, $locked, $amount);
            }

            $notificationService = app(NotificationService::class);
            $notificationService->createForUser(
                $locked->patient_id,
                'consultation_booked',
                'Appointment confirmed',
                'Your consultation has been assigned to a doctor for '.$locked->scheduled_at->format('M j, Y \a\t g:i A').'.',
                ['consultation_id' => $locked->id]
            );
            $notificationService->createForUser(
                $doctor->id,
                'consultation_booked',
                'New appointment',
                'A new consultation has been assigned to you for '.$locked->scheduled_at->format('M j, Y \a\t g:i A').'.',
                ['consultation_id' => $locked->id]
            );
            $notificationService->notifyAdmins(
                'consultation_booked',
                'New consultation booked',
                'A patient has been matched with a doctor for '.$locked->scheduled_at->format('M j, Y \a\t g:i A').'.',
                ['consultation_id' => $locked->id, 'patient_id' => $locked->patient_id, 'doctor_id' => $doctor->id]
            );

            app(AuditLogService::class)->log(
                $doctor,
                'consultation.claimed',
                'Doctor claimed consultation #'.$locked->id.' for '.$locked->scheduled_at,
                Consultation::class,
                $locked->id,
                ['doctor_id' => $doctor->id]
            );

            return new ConsultationResource($locked);
        });
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array{complaint: string, duration: string}>
     */
    private static function normalizePresentingComplaintsPayload(array $rows): array
    {
        $out = [];
        foreach ($rows as $item) {
            if (is_string($item)) {
                $out[] = ['complaint' => $item, 'duration' => ''];
            } elseif (is_array($item)) {
                $c = isset($item['complaint']) && is_string($item['complaint']) ? $item['complaint'] : '';
                $d = isset($item['duration']) && is_string($item['duration']) ? $item['duration'] : '';
                $out[] = ['complaint' => $c, 'duration' => $d];
            } else {
                $out[] = ['complaint' => '', 'duration' => ''];
            }
        }

        return array_values($out);
    }

    /**
     * @param  array<int, mixed>  $rows
     */
    /**
     * @param  mixed  $value  Legacy string or partial array
     * @return array<string, string>
     */
    private static function normalizeReviewOfSystemsPayload(mixed $value): array
    {
        $keys = ['cns', 'respiratory', 'cardiovascular', 'digestive', 'genitourinary', 'locomotor', 'other'];
        $empty = array_fill_keys($keys, '');
        if (is_string($value)) {
            $t = trim($value);
            $out = $empty;
            if ($t !== '') {
                $out['other'] = $value;
            }

            return $out;
        }
        if (! is_array($value)) {
            return $empty;
        }
        $out = $empty;
        foreach ($keys as $k) {
            if (isset($value[$k]) && is_string($value[$k])) {
                $out[$k] = $value[$k];
            }
        }

        return $out;
    }

    private static function flattenPresentingComplaintsForLegacy(array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $c = isset($row['complaint']) && is_string($row['complaint']) ? trim($row['complaint']) : '';
            $d = isset($row['duration']) && is_string($row['duration']) ? trim($row['duration']) : '';
            if ($c === '' && $d === '') {
                continue;
            }
            if ($c === '') {
                $lines[] = '(duration: '.$d.')';
            } elseif ($d === '') {
                $lines[] = $c;
            } else {
                $lines[] = $c.' (duration: '.$d.')';
            }
        }
        if ($lines === []) {
            return '';
        }
        if (count($lines) === 1) {
            return $lines[0];
        }

        $numbered = [];
        foreach ($lines as $i => $line) {
            $numbered[] = ($i + 1).'. '.$line;
        }

        return implode("\n", $numbered);
    }
}
