<?php

return [
    'api_key' => env('STREAM_API_KEY'),
    'api_secret' => env('STREAM_API_SECRET'),
    'default_call_type' => env('STREAM_DEFAULT_CALL_TYPE', 'default'),
    'organization_id' => env('STREAM_ORGANIZATION_ID'),
    'application_id' => env('STREAM_APPLICATION_ID'),
    /** Shared secret so the officer app can resolve phone → stream_user_id (server-to-server). */
    'officer_lookup_token' => env('OFFICER_LOOKUP_TOKEN'),
];
