<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Session Timeout
    |--------------------------------------------------------------------------
    | Idle timeout in minutes for the admin guard. After this many minutes of
    | inactivity the session is invalidated and the admin is redirected to login.
    |
    */
    'session_timeout' => (int) env('ADMIN_SESSION_TIMEOUT', 120),
];
