<?php

return [
    'version' => env('API_VERSION', 'v1'),
    'agent_commission_rate' => (float) env('AGENT_COMMISSION_RATE', 0.05),
];
