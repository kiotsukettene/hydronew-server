<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Clean Water Thresholds (PAES Standards)
    |--------------------------------------------------------------------------
    |
    | These thresholds are based on Philippine Agricultural Engineering Standards
    | for clean water quality in agricultural applications.
    |
    */
    'clean_water' => [
        'ph' => [
            'min' => 6.5,
            'max' => 8.0,
        ],
        'turbidity' => [
            'max' => 5, // NTU
        ],
        'tds' => [
            'max' => 500, // ppm (derived from EC < 1000 µS/cm × 0.5)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | EC to TDS Conversion Factor
    |--------------------------------------------------------------------------
    |
    | The conversion factor to calculate TDS (ppm) from EC (µS/cm).
    | TDS = EC × factor
    |
    | Common factors:
    | - 0.5 (hydroponics standard)
    | - 0.64 (NaCl standard)
    | - 0.7 (442 standard)
    |
    */
    'ec_to_tds_factor' => 0.5,

    /*
    |--------------------------------------------------------------------------
    | Threshold Alert Delay (seconds)
    |--------------------------------------------------------------------------
    |
    | Wait this long after a threshold violation is first detected before
    | sending a notification. Reduces spam and ensures sensors are stably
    | out of range before alerting.
    |
    */
    'alert_delay_seconds' => 120, // 2 minutes
];

