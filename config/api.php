<?php

return [
    'version' => env('API_VERSION', 'v1'),
    'agent_commission_rate' => (float) env('AGENT_COMMISSION_RATE', 0.05),
    'owner' => [
        'initial_user_id' => env('OWNER_INITIAL_USER_ID'),
        'name' => env('OWNER_INITIAL_NAME'),
        'email' => env('OWNER_INITIAL_EMAIL'),
        'phone' => env('OWNER_INITIAL_PHONE'),
        'login_type' => env('OWNER_INITIAL_LOGIN_TYPE', 'email'),
    ],
];
