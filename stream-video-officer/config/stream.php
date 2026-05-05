<?php

return [
    'api_key' => env('STREAM_API_KEY'),
    'api_secret' => env('STREAM_API_SECRET'),
    'default_call_type' => env('STREAM_DEFAULT_CALL_TYPE', 'default'),
    'organization_id' => env('STREAM_ORGANIZATION_ID'),
    'application_id' => env('STREAM_APPLICATION_ID'),
    /** Base URL of the client Laravel app (e.g. http://127.0.0.1:8080) for phone → stream_user_id lookup. */
    'client_app_url' => env('CLIENT_APP_URL'),
    /** Must match OFFICER_LOOKUP_TOKEN on the client app. */
    'officer_lookup_token' => env('OFFICER_LOOKUP_TOKEN'),
];
