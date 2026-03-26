<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Receipt</title>
</head>
<body style="font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; color:#111;">
    <p style="margin:0 0 12px 0;">Hello,</p>

    <p style="margin:0 0 12px 0;">
        This email confirms your payment for your consultation.
    </p>

    <div style="margin:0 0 12px 0; padding: 12px; border: 1px solid #eee; border-radius: 8px;">
        <p style="margin:0 0 6px 0;"><strong>Consultation</strong> #{{ $settlement->consultation?->id ?? $settlement->consultation_id ?? $settlement->id }}</p>
        <p style="margin:0 0 6px 0;"><strong>Date</strong> {{ $settlement->consultation?->scheduled_at?->format('F j, Y \a\t g:i A') }}</p>
        <p style="margin:0 0 6px 0;"><strong>Doctor</strong> {{ $settlement->doctor?->name ?? '—' }}</p>
        <p style="margin:0 0 6px 0;"><strong>Amount paid</strong> {{ number_format((float) $settlement->amount_paid, 2) }} UGX</p>
        <p style="margin:0;"><strong>Status</strong> Receipt email sent</p>
    </div>

    <p style="margin:0 0 12px 0;">
        Please find your PDF receipt attached.
    </p>

    <p style="margin:24px 0 0 0; color:#666;">
        Thank you,<br>
        Dr. O Virtual Consultations
    </p>
</body>
</html>

