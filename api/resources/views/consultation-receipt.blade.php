<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultation Receipt #{{ $settlement->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; color:#111; margin: 24px; }
        h1 { font-size: 16px; margin: 0 0 6px 0; }
        .meta { font-size: 10px; color:#666; margin-bottom: 18px; }
        .box { border:1px solid #e5e5e5; border-radius: 8px; padding: 14px; }
        .row { display:flex; justify-content: space-between; gap: 12px; }
        .label { color:#666; }
        .section { margin-top: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border-bottom: 1px solid #eee; padding: 8px 6px; text-align: left; }
        th { background:#fafafa; }
        .small { font-size: 10px; color:#666; }
    </style>
</head>
<body>
    <h1>Consultation Receipt</h1>
    <div class="meta">
        Receipt ID: {{ $settlement->id }} • Billed at: {{ optional($settlement->created_at)->format('F j, Y \a\t g:i A') }}
    </div>

    <div class="box">
        <div class="row">
            <div>
                <div><span class="label">Patient</span>: {{ $patient->name ?? '—' }}</div>
                <div><span class="label">Email</span>: {{ $patient->email ?? '—' }}</div>
                <div><span class="label">Patient Number</span>: {{ $patient->patient_number ?? '—' }}</div>
            </div>
            <div>
                <div><span class="label">Doctor</span>: {{ $doctor->name ?? '—' }}</div>
                <div><span class="label">Speciality</span>: {{ $doctor?->healthcareProfessional?->speciality ?? '—' }}</div>
            </div>
        </div>

        <div class="section">
            <div class="row">
                <div>
                    <div><span class="label">Consultation</span>: #{{ $consultation->id ?? $settlement->consultation_id ?? $settlement->id }}</div>
                    <div><span class="label">Scheduled</span>: {{ $consultation?->scheduled_at?->format('F j, Y \a\t g:i A') ?? '—' }}</div>
                    <div><span class="label">Type</span>: {{ $consultation?->consultation_type ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="small" style="margin-bottom:6px;">Payment breakdown (UGX)</div>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Amount paid by patient</td>
                        <td>{{ number_format((float) $amountPaid, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Platform fee ({{ number_format((float) $platformFeePercent, 2) }}%)</td>
                        <td>{{ number_format((float) $settlement->platform_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Doctor earning</td>
                        <td>{{ number_format((float) $doctorEarning, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section small" style="margin-top:14px;">
            Thank you for using Dr. O Virtual Consultations.
        </div>
    </div>
</body>
</html>

