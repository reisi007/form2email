<?php
// Define constant to allow config access
define('ACCESS', true);
ini_set('display_errors', '0');

// Include configuration and mailer
$config = include('config.php');
require_once('mailer.php'); // Include the mailer dispatcher

// --- CORS & ORIGIN HANDLING ---
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$isAllowedOrigin = in_array($origin, $config['allowed_origins'] ?? []);

if ($isAllowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Handle preflight requests gracefully
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Checks if all provided fields are in a whitelist (case-insensitive).
 *
 * @param array $fields The array of fields to check (e.g., $_POST).
 * @param array $whitelist The array of allowed field names.
 * @return bool True if all fields are whitelisted, false otherwise.
 */
function areFieldsWhitelisted(array $fields, array $whitelist): bool
{
    $lowercaseWhitelist = array_map('strtolower', $whitelist);
    foreach (array_keys($fields) as $field) {
        if (!in_array(strtolower($field), $lowercaseWhitelist)) {
            return false;
        }
    }
    return true;
}

/**
 * Safely extracts and normalizes the origin from a given URL to prevent open redirect vulnerabilities.
 * It strictly filters against protocol-relative paths and malformed slash combinations.
 *
 * @param string $url The URL to parse and validate.
 * @return string The normalized origin (scheme://host[:port]) or an empty string if invalid.
 */
function getOriginFromUrl(string $url): string
{
    // Block protocol-relative URLs (e.g., //attacker.com) and backslashes to prevent parser bypasses
    if (str_starts_with($url, '//') || str_contains($url, '\\')) {
        return '';
    }

    $parsed = parse_url($url);
    if (!$parsed || empty($parsed['host']) || empty($parsed['scheme'])) {
        return '';
    }

    // Enforce web-safe protocols only
    $scheme = strtolower($parsed['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return '';
    }

    $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
    return $scheme . '://' . strtolower($parsed['host']) . $port;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate honeypot field
    if (!isset($_POST['honeypot']) || $_POST['honeypot'] !== $config['honeypot_value']) {
        http_response_code(403);
        exit('Forbidden');
    }

    // Check if all fields are in the whitelist
    if (!areFieldsWhitelisted($_POST, $config['whitelist'])) {
        http_response_code(400);
        exit('Invalid form fields.');
    }

    // Validate mandatory email field
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        exit('Invalid email address.');
    }

    $userEmail = $_POST['email'];

    // Build email message using only allowed fields
    $message = '';
    foreach ($_POST as $key => $value) {
        // Skip special fields and any fields with an empty value
        if ($key === 'honeypot' || $key === 'subject' || $key === 'subject_prefix' || $key === "_next" || trim((string)$value) === '') {
            continue;
        }
        $message .= ucfirst($key) . ":\n" . htmlspecialchars($value) . "\n\n";
    }

    // Prepare email subject
    $emailSubject = $config['email_subject'];

    if (!empty($_POST['subject'])) {
        $emailSubject = htmlspecialchars($_POST['subject']);
    }

    if (!empty($_POST['subject_prefix'])) {
        $emailSubject = '[' . htmlspecialchars($_POST['subject_prefix']) . '] ' . $emailSubject;
    }

    // --- DYNAMIC PROFILE ROUTING ---
    // Apply the correct sender (and optionally receiver / redirect) based on the HTTP_ORIGIN
    $domainProfiles = $config['domain_profiles'] ?? [];
    $activeProfile = $domainProfiles[$origin] ?? $domainProfiles['default'];

    // Inject dynamic sender into mailer options
    $config['mailer_options']['from_email'] = $activeProfile['from_email'];
    $config['mailer_options']['from_name'] = $activeProfile['from_name'];

    // Override receiver email if specific to the domain profile
    if (!empty($activeProfile['receiver_email'])) {
        $config['receiver_email'] = $activeProfile['receiver_email'];
    }

    // Override redirect URL if specific to the domain profile, fallback to default profile redirect
    if (!empty($activeProfile['redirect_url'])) {
        $config['redirect_url'] = $activeProfile['redirect_url'];
    } else {
        $config['redirect_url'] = $domainProfiles['default']['redirect_url'] ?? '';
    }

    // Fail-safe protection if no redirect URL is defined anywhere in the configuration
    if (empty($config['redirect_url'])) {
        http_response_code(500);
        exit('Server Configuration Error: Missing redirect URL.');
    }

    // Determine the allowed origin for this specific request profile to enforce strict same-origin redirects
    $currentProfileOrigin = '';
    if (array_key_exists($origin, $domainProfiles)) {
        $currentProfileOrigin = $origin;
    } else {
        // Fallback: extract the origin from the default profile's redirect URL
        $currentProfileOrigin = getOriginFromUrl($config['redirect_url']);
    }

    // Frontend override for the redirect (strictly validated using the helper function against the active profile's origin)
    if (!empty($_POST['_next']) && filter_var($_POST['_next'], FILTER_VALIDATE_URL)) {
        $nextOrigin = getOriginFromUrl($_POST['_next']);

        if (!empty($currentProfileOrigin) && $nextOrigin === $currentProfileOrigin) {
            $config['redirect_url'] = $_POST['_next'];
        }
    }

    // Send email using the new mailer function
    $success = send_email(
        $config,
        $emailSubject,
        $message,
        $userEmail
    );

    // --- MAKE.COM WEBHOOK FALLBACK LOGIC ---
    if ($success) {
        header("Location: " . $config['redirect_url']);
        exit;
    } else {
        // Read the Make.com Webhook URL and API Key from the Docker environment
        $webhookUrl = getenv('MAKE_WEBHOOK_URL');
        $makeApiKey = getenv('MAKE_API_KEY');

        if ($webhookUrl && $makeApiKey) {
            // Include the error, the formatted message, and the raw POST data as a fallback
            $payload = json_encode([
                'error' => 'Live Code Error: The form on form.reisinger.pictures failed to send an email via SMTP!',
                'formatted_message' => $message,
                'form_data' => $_POST
            ]);

            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n" .
                        "x-make-apikey: " . $makeApiKey . "\r\n",
                    'content' => $payload,
                    'ignore_errors' => true // Prevent PHP warnings if Make.com is unreachable
                ]
            ];
            $context = stream_context_create($options);
            file_get_contents($webhookUrl, false, $context);
        }

        http_response_code(500);
        exit('Failed to send email.');
    }
}

// Fallback for non-POST requests
http_response_code(405);
exit('Method not allowed.');