<?php
// Define constant to allow config access
define('ACCESS', true);

// Include configuration
$config = include('config.php');

// Function to check if all fields are in the whitelist (case-insensitive)
function areFieldsWhitelisted($fields, $whitelist) {
    $lowercaseWhitelist = array_map('strtolower', $whitelist);
    foreach (array_keys($fields) as $field) {
        if (!in_array(strtolower($field), $lowercaseWhitelist)) {
            return false; // If a field is not in the whitelist, return false
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
        if ($key === 'honeypot') {
            continue; // Skip honeypot in the email body
        }
        $message .= ucfirst($key) . ":\n" . htmlspecialchars($value) . "\n\n";
    }

    // Prepare and send email
    $headers = [
        'From' => $config['receiver_email'], // Use the receiver_email from config
        'Reply-To' => $userEmail
    ];

    $success = mail(
        $config['receiver_email'],
        $config['email_subject'],
        $message,
        $headers
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