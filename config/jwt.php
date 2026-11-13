<?php

return [

    /*
    | Secret key used to sign the JWT access tokens (HMAC SHA-256).
    | Falls back to APP_KEY when JWT_SECRET is not set.
    */

    'secret' => env('JWT_SECRET') ?: env('APP_KEY'),

    /*
    | Access tokens are short-lived (minutes). Refresh tokens live much longer,
    | so the user does not have to log in again (default: 7 days = 10080 minutes).
    */

    'access_ttl' => (int) env('JWT_ACCESS_TTL', 15),

    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 10080),

];
