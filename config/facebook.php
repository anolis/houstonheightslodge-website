<?php

return [
    'app_id' => env('FACEBOOK_APP_ID', '965766727408385'),
    'page_id' => env('FACEBOOK_PAGE_ID'),
    'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN'),
    'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v24.0'),
    'calendar_enabled' => (bool) env('FACEBOOK_CALENDAR_ENABLED', false),
    'snapshot_path' => storage_path('app/private/facebook-events.json'),
];
