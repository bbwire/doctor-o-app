<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical Summary — Consultation {{ $consultation->consultation_number ?? $consultation->id }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            line-height: 1.5;
            color: #1a1a1a;
            margin: 0;
        }
        .doc-border {
            border: 1px solid #c8c8c8;
            padding: 20px 22px 24px;
            min-height: 100%;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e0e0e0;
        }
        .logo-placeholder {
            display: inline-block;
            width: 48px;
            height: 48px;
            line-height: 48px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-size: 11pt;
            font-weight: bold;
            color: #444;
            margin-bottom: 10px;
            text-align: center;
        }
        .brand-name {
            font-size: 17pt;
            font-weight: bold;
            letter-spacing: 0.04em;
            margin: 0 0 4px 0;
            color: #111;
        }
        .brand-sub {
            font-size: 10.5pt;
            color: #333;
            margin: 0;
            font-weight: 600;
        }
        .brand-tagline {
            font-size: 9.5pt;
            color: #666;
            margin: 6px 0 0 0;
            font-style: italic;
        }
        .patient-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .patient-row td {
            vertical-align: top;
            padding: 0;
        }
        .patient-row .patient-line {
            font-size: 10pt;
            line-height: 1.45;
            padding-right: 12px;
        }
        .patient-row .doc-date {
            text-align: right;
            white-space: nowrap;
            font-size: 10pt;
            color: #333;
        }
        .consult-ref {
            font-size: 10pt;
            margin-bottom: 20px;
            color: #333;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #111;
            margin: 0 0 8px 0;
            letter-spacing: 0.02em;
        }
        .section-body {
            margin: 0;
            padding-left: 0;
        }
        .section-body.ul {
            margin: 4px 0 0 0;
            padding-left: 18px;
        }
        .section-body.ol {
            margin: 4px 0 0 0;
            padding-left: 22px;
        }
        .section-body li {
            margin-bottom: 6px;
            white-space: pre-wrap;
        }
        .muted {
            color: #888;
            font-style: italic;
        }
        .final-dx {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed #ddd;
        }
        .final-dx-label {
            font-size: 9.5pt;
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }
        .final-dx-text {
            white-space: pre-wrap;
            margin: 0;
        }
        hr.footer-rule {
            border: none;
            border-top: 1px solid #333;
            margin: 28px 0 14px 0;
        }
        .footer {
            font-size: 9.5pt;
            color: #333;
            line-height: 1.55;
        }
        .footer-line {
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
@php
    /** DomPDF / missing keys: never pass null into count() (PHP 8+). */
    $clinicalSummaryLines = is_array($clinicalSummaryLines ?? null) ? $clinicalSummaryLines : [];
    $provisionalDiagnosisLines = is_array($provisionalDiagnosisLines ?? null) ? $provisionalDiagnosisLines : [];
    $managementPlanItems = is_array($managementPlanItems ?? null) ? $managementPlanItems : [];
    $prescriptionItems = is_array($prescriptionItems ?? null) ? $prescriptionItems : [];
    $issuedPrescriptionBlocks = is_array($issuedPrescriptionBlocks ?? null) ? $issuedPrescriptionBlocks : [];
@endphp
@php
    $patient = $consultation->patient;
    $doctor = $consultation->doctor;
    $hp = $doctor?->healthcareProfessional;
    $consultRef = $consultation->consultation_number
        ? $consultation->consultation_number
        : (string) $consultation->id;
    $docDate = $consultation->scheduled_at?->format('F j, Y') ?? \Illuminate\Support\Carbon::now()->format('F j, Y');
    $ageStr = $patient?->date_of_birth ? (string) $patient->date_of_birth->age : '—';
    $patientNo = $patient?->patient_number ?? '—';
    $patientName = $patient?->name ?? '—';
    $sexStr = '—';
    $residenceStr = '—';
    $demographicBits = [
        '<strong>Name:</strong> ' . e($patientName),
        '<strong>No.:</strong> ' . e($patientNo),
        '<strong>Age:</strong> ' . e($ageStr),
        '<strong>Sex:</strong> ' . e($sexStr),
        '<strong>Residence:</strong> ' . e($residenceStr),
    ];
@endphp

<div class="doc-border">
    <header class="header">
        <div class="logo-placeholder">Dr. O</div>
        <h1 class="brand-name">Dr. O</h1>
        <p class="brand-sub">Virtual Medical Consult</p>
        <p class="brand-tagline">Adding Quality to Life</p>
    </header>

    <table class="patient-row">
        <tr>
            <td class="patient-line">
                {!! implode(' &nbsp;|&nbsp; ', $demographicBits) !!}
            </td>
            <td class="doc-date"><strong>Date:</strong> {{ $docDate }}</td>
        </tr>
    </table>

    <div class="consult-ref"><strong>Consultation No.:</strong> {{ $consultRef }}</div>

    <section class="section">
        <h2 class="section-title">Clinical Summary</h2>
        @if(count($clinicalSummaryLines) > 0)
            <ul class="section-body ul">
                @foreach($clinicalSummaryLines as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        @else
            <p class="muted section-body">Not documented.</p>
        @endif
    </section>

    <section class="section">
        <h2 class="section-title">Provisional Diagnosis</h2>
        @if(count($provisionalDiagnosisLines) > 0)
            <ol class="section-body ol">
                @foreach($provisionalDiagnosisLines as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ol>
        @else
            <p class="muted section-body">Not documented.</p>
        @endif
        @if(!empty($summary['final_diagnosis']) && is_string($summary['final_diagnosis']) && trim($summary['final_diagnosis']) !== '')
            <div class="final-dx">
                <div class="final-dx-label">Final diagnosis</div>
                <p class="final-dx-text">{{ $summary['final_diagnosis'] }}</p>
            </div>
        @endif
    </section>

    <section class="section">
        <h2 class="section-title">Management Plan</h2>
        @if(count($managementPlanItems) > 0)
            <ol class="section-body ol">
                @foreach($managementPlanItems as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ol>
        @else
            <p class="muted section-body">Not documented.</p>
        @endif
    </section>

    <section class="section">
        <h2 class="section-title">Treatment Prescription</h2>
        @if(count($prescriptionItems) > 0)
            <ol class="section-body ol">
                @foreach($prescriptionItems as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ol>
        @else
            <p class="muted section-body">Not documented.</p>
        @endif
    </section>

    @if(count($issuedPrescriptionBlocks) > 0)
        <section class="section">
            <h2 class="section-title">Issued prescriptions (digital Rx)</h2>
            @foreach($issuedPrescriptionBlocks as $block)
                <p class="section-body" style="font-weight: bold; margin-bottom: 6px;">{{ $block['heading'] ?? '' }}</p>
                @if(!empty($block['lines']) && is_array($block['lines']))
                    <ol class="section-body ol">
                        @foreach($block['lines'] as $rxLine)
                            <li>{{ $rxLine }}</li>
                        @endforeach
                    </ol>
                @endif
            @endforeach
        </section>
    @endif

    <hr class="footer-rule">

    <footer class="footer">
        <div class="footer-line">
            <strong>Doctor:</strong>
            {{ $doctor?->name ?? '—' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>Phone:</strong>
            {{ $doctor?->phone ?? '—' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>Dr. O platform no.:</strong>
            {{ $doctor?->id ?? '—' }}
        </div>
        <div class="footer-line">
            <strong>Council:</strong>
            {{ $hp?->regulatory_council ?? '—' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>Reg. no.:</strong>
            {{ $hp?->professional_number ?? '—' }}
            @if(!empty($hp?->license_number))
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <strong>License:</strong>
                {{ $hp->license_number }}
            @endif
        </div>
    </footer>
</div>
</body>
</html>
