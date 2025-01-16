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

    // Check for disallowed keys (starting with "_")
    foreach ($_POST as $key => $value) {
        if (str_starts_with($key, '_')) {
            http_response_code(400);
            exit('Invalid form data.');
        }
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
        // Separate key and value with a line break
        $message .= ucfirst($key) . ":\n" . htmlspecialchars($value) . "\n\n";
    }

    // Prepare and send email
    $headers = [
        'From' => $config['receiver_email'],
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
