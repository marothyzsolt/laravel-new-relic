<?php

use Symfony\Component\HttpFoundation\Request;

return [
    'app_name' => env('NEW_RELIC_APP_NAME', 'Laravel'),
    'license_key' => env('NEW_RELIC_LICENSE_KEY'),

    'extra_data' => function (?Request $request, ?Throwable $throwable): array {
        return [];
    }
];
