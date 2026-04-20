<?php

$appEnv = (string) env('APP_ENV', '');
$updateGithubTlsRelaxedByDefault = $appEnv === '' || in_array($appEnv, ['local', 'testing'], true);

return [
    'github' => [
        'owner' => env('UPDATE_GITHUB_OWNER', ''),
        'repo' => env('UPDATE_GITHUB_REPO', ''),
        // Optional for public repos; required for private repos / higher rate limits.
        'token' => env('UPDATE_GITHUB_TOKEN'),
        // You should upload a built artifact with this exact name to the GitHub Release.
        'asset_name' => env('UPDATE_GITHUB_ASSET_NAME', 'release.zip'),
        // Windows: point at https://curl.se/ca/cacert.pem if you see cURL error 60. Prefer this over relaxed TLS.
        'curl_ca_bundle' => env('UPDATE_GITHUB_CURL_CAINFO', ''),
        // Staging/production: strict TLS. Local/testing (or missing APP_ENV): relaxed for GitHub HTTP client only.
        'verify_ssl' => filter_var(
            env('UPDATE_GITHUB_VERIFY_SSL', $updateGithubTlsRelaxedByDefault ? 'false' : 'true'),
            FILTER_VALIDATE_BOOLEAN
        ),
        // Seconds (cURL error 28 = timeout). API default was ~10s; slow networks need more.
        'http_timeout_api' => max(5, (int) env('UPDATE_GITHUB_HTTP_TIMEOUT_API', 120)),
        'http_timeout_download' => max(30, (int) env('UPDATE_GITHUB_HTTP_TIMEOUT_DOWNLOAD', 1800)),
    ],

    // WARNING: Changing APP_KEY breaks encrypted data/sessions.
    'allow_app_key_regen' => (bool) env('UPDATE_ALLOW_APP_KEY_REGEN', false),
];
