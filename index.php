<?php
// Define constant to allow config access
define('ACCESS', true);

// Include configuration
$config = include('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate mandatory email field
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        exit('Invalid email address.');
    }

    $userEmail = $_POST['email'];
    $message = '';
    foreach ($_POST as $key => $value) {
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