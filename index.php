<?php
// Define constant to allow config access
define('ACCESS', true);

// Include configuration and mailer
$config = include('config.php');
require_once('mailer.php'); // Include the mailer dispatcher

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
        if ($key === 'honeypot' || $key === 'subject_prefix' || trim((string) $value) === '') {
            continue;
        }
        $message .= ucfirst($key) . ":\n" . htmlspecialchars($value) . "\n\n";
    }

    // Prepare email subject
    $emailSubject = $config['email_subject'];
    if (!empty($_POST['subject_prefix'])) {
        $emailSubject = '[' . htmlspecialchars($_POST['subject_prefix']) . '] ' . $emailSubject;
    }

    // Send email using the new mailer function
    $success = send_email(
        $config,
        $emailSubject,
        $message,
        $userEmail
    );

    if ($success) {
        header("Location: " . $config['redirect_url']);
        exit;
    } else {
        http_response_code(500);
        exit('Failed to send email.');
    }
}

// Fallback for non-POST requests
http_response_code(405);
exit('Method not allowed.');
