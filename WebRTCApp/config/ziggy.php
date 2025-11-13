<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ziggy Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Ziggy to use the request URL instead of APP_URL
    | This ensures routes work correctly regardless of the domain or port
    |
    */

    'url' => null, // Use current request URL instead of config('app.url')
];
