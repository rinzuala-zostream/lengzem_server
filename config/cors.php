<?php

return [

    'paths' => ['api/*', 'content/*'],  // the routes you want CORS enabled for

    'allowed_methods' => ['*'],         // allow all HTTP methods

    'allowed_origins' => [
        'https://app.lengzem.in',
        'https://lengzem.in'
    ],  // allow your frontend origin

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],         // allow all headers

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,    // true if you use cookies/auth headers
];
