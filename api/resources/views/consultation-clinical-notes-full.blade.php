<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultation clinical record #{{ $consultation->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; color: #333; margin: 22px; }
        h1 { font-size: 15px; margin-bottom: 6px; color: #111; }
        h2 { font-size: 11px; margin-top: 14px; margin-bottom: 5px; color: #222; border-bottom: 1px solid #ddd; padding-bottom: 2px; }
        .meta { font-size: 9px; color: #555; margin-bottom: 14px; }
        .section { margin-bottom: 10px; }
        .section-content { white-space: pre-wrap; margin-top: 3px; }
        .empty { color: #999; font-style: italic; }
        .msg { margin-bottom: 8px; padding: 6px; background: #f7f7f7; border-radius: 3px; }
        .msg-meta { font-size: 8px; color: #666; margin-bottom: 2px; }
        ul.icd { margin: 4px 0 0 16px; padding: 0; }
    </style>
</head>
<body>

<h1>Consultation clinical record</h1>
<div class="meta">
    Consultation #{{ $consultation->id }} &bull;
    {{ $consultation->scheduled_at?->format('F j, Y \a\t g:i A') }} &bull;
    Type: {{ $consultation->consultation_type }} &bull; Status: {{ $consultation->status }}<br>
    Dr. {{ $consultation->doctor?->name ?? 'Unknown' }} &bull;
    Patient: {{ $consultation->patient?->name ?? 'Unknown' }}
    @if(!empty($consultation->patient?->patient_number))
        &bull; Patient no.: {{ $consultation->patient->patient_number }}
    @endif
</div>

@if(!empty($consultation->reason))
    <div class="section">
        <h2>Reason for consultation</h2>
        <div class="section-content">{{ strip_tags($consultation->reason) }}</div>
    </div>
@endif

@if(!empty($consultation->notes))
    <div class="section">
        <h2>Consultation notes (internal)</h2>
        <div class="section-content">{{ $consultation->notes }}</div>
    </div>
@endif

@php
    $presentingComplaintLines = [];
    if (!empty($notes['presenting_complaints']) && is_array($notes['presenting_complaints'])) {
        foreach ($notes['presenting_complaints'] as $line) {
            if (is_string($line) && trim($line) !== '') {
                $presentingComplaintLines[] = trim($line);
            } elseif (is_array($line)) {
                $c = isset($line['complaint']) && is_string($line['complaint']) ? trim($line['complaint']) : '';
                $d = isset($line['duration']) && is_string($line['duration']) ? trim($line['duration']) : '';
                if ($c === '' && $d === '') {
                    continue;
                }
                if ($c === '') {
                    $presentingComplaintLines[] = '(duration: '.$d.')';
                } elseif ($d === '') {
                    $presentingComplaintLines[] = $c;
                } else {
                    $presentingComplaintLines[] = $c.' — Duration: '.$d;
                }
            }
        }
    }
@endphp
@if(count($presentingComplaintLines) > 0)
    <div class="section">
        <h2>Presenting complaint(s)</h2>
        <ol style="margin: 4px 0 0 18px; padding: 0;">
            @foreach($presentingComplaintLines as $line)
                <li style="white-space: pre-wrap; margin-top: 3px;">{{ $line }}</li>
            @endforeach
        </ol>
    </div>
@elseif(!empty($notes['presenting_complaint']) && is_string($notes['presenting_complaint']) && trim($notes['presenting_complaint']) !== '')
    <div class="section">
        <h2>Presenting complaint</h2>
        <div class="section-content">{{ $notes['presenting_complaint'] }}</div>
    </div>
@endif

@php
    $ros = $notes['review_of_systems'] ?? null;
    $rosBlocks = [];
    if (is_string($ros) && trim($ros) !== '') {
        $rosBlocks[] = ['label' => null, 'text' => trim($ros)];
    } elseif (is_array($ros)) {
        $rosDefs = [
            'cns' => 'Central nervous system',
            'respiratory' => 'Respiratory system',
            'cardiovascular' => 'Cardiovascular system',
            'digestive' => 'Digestive system',
            'genitourinary' => 'Genital–urinary system',
            'locomotor' => 'Locomotor system',
            'other' => 'Other systems',
        ];
        foreach ($rosDefs as $rk => $rlabel) {
            $t = isset($ros[$rk]) && is_string($ros[$rk]) ? trim($ros[$rk]) : '';
            if ($t !== '') {
                $rosBlocks[] = ['label' => $rlabel, 'text' => $t];
            }
        }
    }
@endphp
@if(count($rosBlocks) > 0)
    <div class="section">
        <h2>Review of systems</h2>
        @foreach($rosBlocks as $rb)
            @if(!empty($rb['label']))
                <p style="font-weight:600;margin:10px 0 4px;font-size:11px;color:#444;">{{ $rb['label'] }}</p>
            @endif
            <div class="section-content" style="margin-bottom:6px;">{{ $rb['text'] }}</div>
        @endforeach
    </div>
@endif

@php
    $simpleTextFields = [
        'history_of_presenting_complaint' => 'History of presenting complaint',
        'past_medical_history' => 'Past medical history',
        'past_surgical_history' => 'Past surgical history',
        'growth_and_development' => 'Growth and development',
        'immunization_history' => 'Immunization history',
        'family_history' => 'Family history',
        'social_history' => 'Social history',
        'summary_of_history' => 'Summary of history',
        'differential_diagnosis' => 'Differential diagnosis',
        'investigation_results' => 'Investigation results',
        'final_diagnosis' => 'Final diagnosis',
        'final_treatment' => 'Final treatment',
    ];
@endphp

@foreach($simpleTextFields as $key => $label)
    @if(!empty($notes[$key]) && is_string($notes[$key]) && trim($notes[$key]) !== '')
        <div class="section">
            <h2>{{ $label }}</h2>
            <div class="section-content">{{ $notes[$key] }}</div>
        </div>
    @endif
@endforeach

@if(!empty($notes['differential_diagnoses_icd11']) && is_array($notes['differential_diagnoses_icd11']))
    <div class="section">
        <h2>Differential diagnoses (ICD-11)</h2>
        <ul class="icd">
            @foreach($notes['differential_diagnoses_icd11'] as $dx)
                @if(is_array($dx) && (!empty($dx['code'] ?? null) || !empty($dx['title'] ?? null)))
                    <li>{{ $dx['code'] ?? '' }} {{ $dx['title'] ?? '' }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($notes['final_diagnosis_icd11']) && is_array($notes['final_diagnosis_icd11']))
    @php $fd = $notes['final_diagnosis_icd11']; @endphp
    @if(!empty($fd['code'] ?? null) || !empty($fd['title'] ?? null))
        <div class="section">
            <h2>Final diagnosis (ICD-11)</h2>
            <div class="section-content">{{ $fd['code'] ?? '' }} {{ $fd['title'] ?? '' }}</div>
        </div>
    @endif
@endif

@php
    $mp = $notes['management_plan'] ?? null;
    $isStructured = is_array($mp);
    $ipv = $isStructured ? ($mp['in_person_visit'] ?? null) : null;
    $hasIpv = is_array($ipv) && (!empty($ipv['revisit_history'] ?? null) || !empty($ipv['general_examination'] ?? null) || !empty($ipv['system_examination'] ?? null));
    $rx = $isStructured ? ($mp['prescription'] ?? null) : null;
    $hasRx = is_array($rx) && !empty($rx['medications'] ?? null);
    $hasMpContent = $isStructured && (!empty($mp['treatment'] ?? null) || !empty($mp['investigation_radiology'] ?? null) || !empty($mp['investigation_laboratory'] ?? null) || !empty($mp['investigation_interventional'] ?? null) || !empty($mp['referrals'] ?? null) || $hasIpv || $hasRx);
@endphp

@if ($hasMpContent)
    <div class="section">
        <h2>Management plan</h2>
        @if(!empty($mp['treatment'] ?? null))
            <p style="font-weight: bold; margin-top: 6px;">Treatment</p>
            <div class="section-content">{{ $mp['treatment'] }}</div>
        @endif
        @if($hasRx)
            <p style="font-weight: bold; margin-top: 8px;">Prescription</p>
            <ul style="margin: 4px 0 0 16px;">
                @foreach(($rx['medications'] ?? []) as $med)
                    @if(is_array($med) && !empty(trim($med['name'] ?? '')))
                        <li>
                            {{ $med['name'] }}
                            @if(!empty($med['form'] ?? null)) ({{ $med['form'] }}) @endif
                            @if(!empty($med['dosage'] ?? null)) — {{ $med['dosage'] }} @endif
                            @if(!empty($med['frequency'] ?? null)), {{ $med['frequency'] }} @endif
                            @if(!empty($med['duration'] ?? null)) ({{ $med['duration'] }}) @endif
                            @if(!empty($med['instructions'] ?? null))
                                <br><span style="font-size: 9px; color: #555;">{{ $med['instructions'] }}</span>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
            @if(!empty($rx['instructions'] ?? null))
                <div class="section-content" style="margin-top: 4px;">{{ $rx['instructions'] }}</div>
            @endif
        @endif
        @if(!empty($mp['investigation_radiology'] ?? null) || !empty($mp['investigation_laboratory'] ?? null) || !empty($mp['investigation_interventional'] ?? null))
            <p style="font-weight: bold; margin-top: 8px;">Investigation</p>
            @if(!empty($mp['investigation_radiology'] ?? null))
                <p><strong>Radiology:</strong> {{ $mp['investigation_radiology'] }}</p>
            @endif
            @if(!empty($mp['investigation_laboratory'] ?? null))
                <p><strong>Laboratory:</strong> {{ $mp['investigation_laboratory'] }}</p>
            @endif
            @if(!empty($mp['investigation_interventional'] ?? null))
                <p><strong>Interventional:</strong> {{ $mp['investigation_interventional'] }}</p>
            @endif
        @endif
        @if(!empty($mp['referrals'] ?? null))
            <p style="font-weight: bold; margin-top: 8px;">Referrals</p>
            <div class="section-content">{{ $mp['referrals'] }}</div>
        @endif
        @if($hasIpv)
            <p style="font-weight: bold; margin-top: 8px;">In-person visit</p>
            @if(!empty($ipv['revisit_history'] ?? null))
                <p><strong>Doctor revisits history:</strong></p>
                <div class="section-content">{{ $ipv['revisit_history'] }}</div>
            @endif
            @if(!empty($ipv['general_examination'] ?? null))
                <p><strong>General examination:</strong></p>
                @php $ge = $ipv['general_examination']; @endphp
                @if(is_array($ge))
                    @php
                        $geLines = [];
                        if(!empty($ge['appearance'] ?? null)) $geLines[] = 'General appearance: ' . $ge['appearance'];
                        if(!empty($ge['jaundice'] ?? null)) $geLines[] = 'Jaundice: ' . $ge['jaundice'];
                        if(!empty($ge['anemia'] ?? null)) $geLines[] = 'Anemia: ' . $ge['anemia'];
                        if(!empty($ge['cyanosis'] ?? null)) $geLines[] = 'Cyanosis: ' . $ge['cyanosis'];
                        if(!empty($ge['clubbing'] ?? null)) $geLines[] = 'Clubbing: ' . $ge['clubbing'];
                        if(!empty($ge['oedema'] ?? null)) $geLines[] = 'Oedema: ' . $ge['oedema'];
                        if(!empty($ge['lymphadenopathy'] ?? null)) $geLines[] = 'Lymphadenopathy: ' . $ge['lymphadenopathy'];
                        if(!empty($ge['dehydration'] ?? null)) $geLines[] = 'Dehydration: ' . $ge['dehydration'];
                    @endphp
                    <div class="section-content">{{ implode("\n", $geLines) }}</div>
                @else
                    <div class="section-content">{{ $ge }}</div>
                @endif
            @endif
            @if(!empty($ipv['system_examination'] ?? null))
                <p><strong>System examination:</strong></p>
                <div class="section-content">{{ is_string($ipv['system_examination']) ? $ipv['system_examination'] : json_encode($ipv['system_examination']) }}</div>
            @endif
        @elseif(is_string($ipv) && trim($ipv) !== '')
            <p style="font-weight: bold; margin-top: 8px;">In-person visit</p>
            <div class="section-content">{{ $ipv }}</div>
        @endif
    </div>
@elseif (is_string($mp) && trim($mp) !== '')
    <div class="section">
        <h2>Management plan</h2>
        <div class="section-content">{{ $mp }}</div>
    </div>
@endif

@php
    $oc = $notes['outcome'] ?? null;
    $hasOutcome = is_array($oc) && (
        (!empty($oc['doctor_notes']) && is_string($oc['doctor_notes']) && trim($oc['doctor_notes']) !== '')
        || array_key_exists('patient_reports_improved', $oc)
    );
@endphp
@if($hasOutcome)
    <div class="section">
        <h2>Outcome</h2>
        @if(!empty($oc['doctor_notes']) && is_string($oc['doctor_notes']) && trim($oc['doctor_notes']) !== '')
            <p style="font-weight: bold; margin-top: 4px;">Clinician</p>
            <div class="section-content">{{ $oc['doctor_notes'] }}</div>
        @endif
        @if(array_key_exists('patient_reports_improved', $oc))
            <p style="font-weight: bold; margin-top: 8px;">Patient follow-up</p>
            <p class="section-content">
                Reports improved:
                @if($oc['patient_reports_improved'] === true)
                    Yes
                @elseif($oc['patient_reports_improved'] === false)
                    No
                @else
                    Not recorded
                @endif
                @if(!empty($oc['patient_reported_at']))
                    <span style="color:#666;font-size:9px;"> ({{ $oc['patient_reported_at'] }})</span>
                @endif
            </p>
        @endif
    </div>
@endif

@if(count($uploads) > 0)
    <div class="section">
        <h2>Patient-uploaded investigation files</h2>
        @foreach($uploads as $u)
            @if(is_array($u))
                <p style="margin: 4px 0;">
                    <strong>{{ ($u['category'] ?? '') === 'radiology' ? 'Radiology' : 'Laboratory' }}:</strong>
                    {{ $u['label'] ?? $u['original_filename'] ?? 'File' }}
                </p>
            @endif
        @endforeach
    </div>
@endif

</body>
</html>
