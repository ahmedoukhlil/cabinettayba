<?php

return [
    'public_cabinet_id' => (int) env('PUBLIC_CABINET_ID', 1),
    'booking_horizon_days' => (int) env('PUBLIC_BOOKING_HORIZON_DAYS', 90),
    'opening_time' => env('PUBLIC_BOOKING_OPENING_TIME', '08:00'),
    'closing_time' => env('PUBLIC_BOOKING_CLOSING_TIME', '18:00'),
    'slot_duration_minutes' => (int) env('PUBLIC_BOOKING_SLOT_DURATION', 30),
];
