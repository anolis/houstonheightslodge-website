<?php

// Shared helper — POST JSON payload to the GAS web app and return decoded response.
function gasPost(array $payload): array
{
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode($payload),
        'timeout' => 12,
        'follow_location' => 1,
        'max_redirects' => 5,
    ]]);
    $raw = @file_get_contents(GAS_URL, false, $ctx);
    if ($raw === false) {
        return ['error' => 'Registry unavailable. Please try again.'];
    }
    $data = json_decode($raw, true);

    return $data ?? ['error' => 'Invalid response from registry.'];
}
