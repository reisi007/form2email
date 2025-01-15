<?php
// Define constant to allow config access
define('ACCESS', true);

// Include configuration
$config = include('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate honeypot field
    if (!isset($_POST['honeypot']) || $_POST['honeypot'] !== $config['honeypot_value']) {
        http_response_code(403);
        exit('Forbidden');
    }

    // Validate mandatory email field
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        exit('Invalid email address.');
    }

    $userEmail = $_POST['email'];

    // Build email message (exclude honeypot)
    $message = '';
    foreach ($_POST as $key => $value) {
        if ($key === 'honeypot') {
            continue; // Skip honeypot
        }
        $message .= ucfirst($key) . ": " . htmlspecialchars($value) . "\n";
    }

    // Prepare and send email
    $headers = [
        'From' => 'no-reply@example.com',
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
