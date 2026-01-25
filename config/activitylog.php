<?php

return [
    // Global toggle for activity logging
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),
    
    // Custom: Toggle activity logging for each model
    'model_logging' => [
        'user' => true,
        'role' => true,
        'permission' => true,
        'activity_log' => true,
    ],
];
