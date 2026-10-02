<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'pdf/generate/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Em produção, substitua por seus domínios
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];