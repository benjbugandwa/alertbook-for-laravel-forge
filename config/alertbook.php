<?php

return [
    'sla_notifications_enabled' => (bool) env('ALERTBOOK_SLA_NOTIFICATIONS_ENABLED', false),
    'sla_notification_time' => env('ALERTBOOK_SLA_NOTIFICATION_TIME', '07:00'),

    'bootstrap_admin' => [
        'name' => env('ALERTBOOK_BOOTSTRAP_ADMIN_NAME'),
        'email' => env('ALERTBOOK_BOOTSTRAP_ADMIN_EMAIL'),
        'password' => env('ALERTBOOK_BOOTSTRAP_ADMIN_PASSWORD'),
    ],

    'documentation' => [
        'driver' => env('ALERTBOOK_DOCUMENTATION_DRIVER', 'local'),
        'local_path' => env('ALERTBOOK_DOCUMENTATION_PATH', 'C:/DATA/PROJET ALERTBOOK 2026/Documentation'),
        'disk' => env('ALERTBOOK_DOCUMENTATION_DISK', 's3'),
        'prefix' => trim((string) env('ALERTBOOK_DOCUMENTATION_PREFIX', 'documentation/videos'), '/'),
        'temporary_url_ttl' => (int) env('ALERTBOOK_DOCUMENTATION_URL_TTL', 3600),
    ],
];
