<?php

declare(strict_types=1);

$oauthClientId     = getenv('OAUTH_CLIENT_ID');
$oauthClientSecret = getenv('OAUTH_CLIENT_SECRET');
$refreshToken      = getenv('OAUTH_REFRESH_TOKEN');
$webhookUrl        = getenv('MAKE_WEBHOOK_URL');
$apiKey           = getenv('MAKE_API_KEY');
$interval          = (int) (getenv('CHECK_INTERVAL') ?: 86400);

echo '[' . date('Y-m-d H:i:s') . "] OAuth2 Token Checker started. Checking every {$interval} seconds.\n";

while (true) {
    echo '[' . date('Y-m-d H:i:s') . "] Initiating OAuth2 token check...\n";

    $httpContext = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'client_id'     => $oauthClientId,
                'client_secret' => $oauthClientSecret,
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
            ]),
        ],
    ]);

    $response = @file_get_contents('https://oauth2.googleapis.com/token', false, $httpContext);

    if ($response === false || str_contains($response, '"error"')) {
        echo '[' . date('Y-m-d H:i:s') . "] ERROR: Google OAuth2 token renewal failed! Sending Make.com webhook...\n";

        $webhookContext = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nx-make-apikey: {$apiKey}",
                'content' => json_encode([
                    'error'             => 'Automated Checker: Google OAuth2 Refresh Token for form.reisinger.pictures has expired or is invalid!',
                    'formatted_message' => 'System Alert - Check Portainer Logs.',
                ]),
            ],
        ]);

        @file_get_contents($webhookUrl, false, $webhookContext);
    } else {
        echo '[' . date('Y-m-d H:i:s') . "] SUCCESS: OAuth2 Token is valid and can be successfully refreshed.\n";
    }

    $nextTime = date('Y-m-d H:i:s', time() + $interval);
    echo '[' . date('Y-m-d H:i:s') . "] Next check scheduled for: {$nextTime}\n";

    sleep($interval);
}
