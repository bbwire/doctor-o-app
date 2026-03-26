<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Summary - #{{ $consultation->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; color: #333; margin: 24px; }
        h1 { font-size: 16px; margin-bottom: 4px; color: #111; }
        h2 { font-size: 13px; margin-top: 16px; margin-bottom: 6px; color: #222; }
        .meta { font-size: 10px; color: #666; margin-bottom: 16px; }
        .section { margin-bottom: 14px; }
        .section-content { white-space: pre-wrap; margin-top: 4px; }
        .empty { color: #999; font-style: italic; }
    </style>
</head>
<body>
    <h1>Consultation Summary</h1>
    <div class="meta">
        Consultation #{{ $consultation->id }} &bull;
        {{ $consultation->scheduled_at?->format('F j, Y \a\t g:i A') }} &bull;
        Dr. {{ $consultation->doctor?->name ?? 'Unknown' }} &bull;
        Patient: {{ $consultation->patient?->name ?? 'Unknown' }}
    </div>

    <div class="section">
        <h2>Summary of History</h2>
        <div class="section-content {{ empty($summary['summary_of_history']) ? 'empty' : '' }}">
            {{ $summary['summary_of_history'] ?? 'Not documented.' }}
        </div>
    </div>

    <div class="section">
        <h2>Differential Diagnosis</h2>
        <div class="section-content {{ empty($summary['differential_diagnosis']) ? 'empty' : '' }}">
            {{ $summary['differential_diagnosis'] ?? 'Not documented.' }}
        </div>
    </div>

    <div class="section">
        <h2>Final Diagnosis</h2>
        <div class="section-content {{ empty($summary['final_diagnosis']) ? 'empty' : '' }}">
            {{ $summary['final_diagnosis'] ?? 'Not documented.' }}
        </div>
    </div>

    <div class="section">
        <h2>Management Plan</h2>
        @php
            $mp = $summary['management_plan'] ?? null;
            $isStructured = is_array($mp);
            $ipv = $isStructured ? ($mp['in_person_visit'] ?? null) : null;
            $hasIpv = is_array($ipv) && (!empty($ipv['revisit_history'] ?? null) || !empty($ipv['general_examination'] ?? null) || !empty($ipv['system_examination'] ?? null));
            $rx = $isStructured ? ($mp['prescription'] ?? null) : null;
            $hasRx = is_array($rx) && !empty($rx['medications'] ?? null);
            $hasMpContent = $isStructured && (!empty($mp['treatment'] ?? null) || !empty($mp['investigation_radiology'] ?? null) || !empty($mp['investigation_laboratory'] ?? null) || !empty($mp['investigation_interventional'] ?? null) || !empty($mp['referrals'] ?? null) || $hasIpv || $hasRx);
        @endphp
        @if ($hasMpContent)
            @if(!empty($mp['treatment'] ?? null))
                <p style="font-weight: bold; margin-top: 8px;">Treatment</p>
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
                <p style="font-weight: bold; margin-top: 8px;">In-Person Visit</p>
                @if(!empty($ipv['revisit_history'] ?? null))
                    <p><strong>Doctor revisits history:</strong></p>
                    <div class="section-content">{{ $ipv['revisit_history'] }}</div>
                @endif
                @if(!empty($ipv['general_examination'] ?? null))
                    <p><strong>General examination:</strong></p>
                    @php
                        $ge = $ipv['general_examination'] ?? null;
                    @endphp
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
                    <div class="section-content">{{ $ipv['system_examination'] }}</div>
                @endif
            @elseif(is_string($ipv) && trim($ipv) !== '')
                <p style="font-weight: bold; margin-top: 8px;">In-Person Visit</p>
                <div class="section-content">{{ $ipv }}</div>
            @endif
        @elseif (is_string($mp) && trim($mp) !== '')
            <div class="section-content">{{ $mp }}</div>
        @else
            <div class="section-content empty">Not documented.</div>
        @endif
    </div>
</body>
</html>
