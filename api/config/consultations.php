<?php

return [
    'slot_interval_minutes' => (int) env('CONSULTATION_SLOT_INTERVAL_MINUTES', 60),
    'availability_window_days' => (int) env('CONSULTATION_AVAILABILITY_WINDOW_DAYS', 14),
    'minimum_action_lead_hours' => (int) env('CONSULTATION_MIN_ACTION_LEAD_HOURS', 2),
];
