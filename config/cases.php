<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Attachment Upload Ceiling
    |--------------------------------------------------------------------------
    |
    | Maximum accepted upload size in kilobytes. Keep this at or below the
    | web server's client_max_body_size / upload_max_filesize.
    |
    */

    'upload_max_kilobytes' => (int) env('UPLOAD_MAX_KILOBYTES', 10240),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Pagination
    |--------------------------------------------------------------------------
    |
    | The case register is always paginated server-side; the full table is
    | never loaded into memory.
    |
    */

    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Overdue Threshold
    |--------------------------------------------------------------------------
    |
    | The AC office's service standard, in days. An open case older than this
    | is counted on the Overdue stat card and flagged in the register.
    |
    */

    'overdue_after_days' => (int) env('CASE_OVERDUE_AFTER_DAYS', 15),

    /*
    |--------------------------------------------------------------------------
    | Office attribution (email footer)
    |--------------------------------------------------------------------------
    */

    'office_name' => env(
        'CASE_OFFICE_NAME',
        'Office of the Assistant Commissioner',
    ),

    /*
    |--------------------------------------------------------------------------
    | Cache Lifetimes (seconds)
    |--------------------------------------------------------------------------
    |
    | Dashboard statistics and the department picker tolerate a little
    | staleness in exchange for not re-running aggregate queries on every
    | request. Exact real-time precision is not a requirement here.
    |
    */

    'cache' => [
        'stats_ttl' => 30,
        'departments_ttl' => 60,
    ],

];
