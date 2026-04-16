<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescription {{ $prescription->prescription_number ?? $prescription->id }}</title>
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
        .header { text-align: center; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid #e0e0e0; }
        .brand-name { font-size: 16pt; font-weight: bold; margin: 0 0 4px 0; }
        .rx-title { font-size: 12pt; font-weight: bold; margin: 16px 0 8px 0; }
        .meta { font-size: 10pt; color: #333; margin-bottom: 14px; }
        .section-title { font-size: 11pt; font-weight: bold; margin: 12px 0 6px 0; }
        ol { margin: 4px 0 0 0; padding-left: 22px; }
        li { margin-bottom: 8px; white-space: pre-wrap; }
        .instructions { margin-top: 12px; padding: 10px; background: #f7f7f7; border: 1px solid #e5e5e5; white-space: pre-wrap; }
        .footer { margin-top: 22px; padding-top: 12px; border-top: 1px solid #333; font-size: 9pt; color: #333; }
        .muted { color: #666; font-size: 9pt; font-style: italic; margin-top: 14px; }
    </style>
</head>
<body>
@php
    $patient = $prescription->patient;
    $doctor = $prescription->doctor;
    $hp = $doctor?->healthcareProfessional;
    $rxLines = $rxLines ?? [];
@endphp
<div class="doc-border">
    <header class="header">
        <h1 class="brand-name">Dr. O</h1>
        <p style="margin:0;font-size:10pt;">Prescription document</p>
    </header>

    <p class="rx-title">Rx {{ $prescription->prescription_number ?? ('#'.$prescription->id) }}</p>
    <p class="meta">
        <strong>Patient:</strong> {{ $patient?->name ?? '—' }}
        @if($patient?->patient_number)
            &nbsp;|&nbsp; <strong>No.:</strong> {{ $patient->patient_number }}
        @endif
        <br>
        <strong>Issued:</strong> {{ $prescription->issued_at?->format('F j, Y g:i A') ?? '—' }}
        <br>
        <strong>Prescriber:</strong> {{ $doctor?->name ?? '—' }}
    </p>

    <div class="section-title">Medications</div>
    @if(count($rxLines) > 0)
        <ol>
            @foreach($rxLines as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ol>
    @else
        <p class="muted">No medication lines recorded.</p>
    @endif

    <div class="footer">
        @if($hp?->regulatory_council)
            <strong>Council:</strong> {{ $hp->regulatory_council }}
        @endif
        @if($hp?->professional_number)
            &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Reg. no.:</strong> {{ $hp->professional_number }}
        @endif
        @if($hp?->license_number)
            &nbsp;&nbsp;|&nbsp;&nbsp; <strong>License:</strong> {{ $hp->license_number }}
        @endif
    </div>

    <p class="muted">
        Present this document to your pharmacy. After you collect your medication, confirm receipt in the Dr. O app so this prescription is archived from your active list.
    </p>
</div>
</body>
</html>
