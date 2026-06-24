<?php
// Prevent direct access
if (!defined('ACCESS')) {
    die('Direct access not permitted.');
}

return [
    // --- General Settings ---
    'receiver_email' => 'your-email@example.com',
    'redirect_url' => 'https://example.com/thankyou.html',
    'email_subject' => 'New Form Submission',
    'honeypot_value' => 'a-unique-and-secret-value',
    // Case-insensitive whitelist of allowed form fields.
    // 'email' and 'honeypot' are required. 'subject_prefix' is optional.
    'whitelist' => ['email', 'name', 'message', 'honeypot', 'subject', 'subject_prefix'],

    // --- CORS & Domain Routing Settings ---
    // List of allowed origins for CORS headers
    'allowed_origins' => [
        'https://reisinger.pictures',
        'https://all-the.rest'
    ],

    // Dynamic profiles based on the HTTP_ORIGIN
    // This allows routing to different sender aliases, receiving addresses, and redirect URLs
    'domain_profiles' => [
        'https://reisinger.pictures' => [
            'from_email' => 'b2c@reisinger.pictures',
            'from_name' => 'Reisinger Pictures (B2C)',
            'receiver_email' => 'b2c-inquiries@reisinger.pictures',
            'redirect_url' => 'https://reisinger.pictures/danke.html'
        ],
        'https://all-the.rest' => [
            'from_email' => 'b2b@all-the.rest',
            'from_name' => 'All-The Rest (B2B)',
            'receiver_email' => 'b2b-leads@all-the.rest',
            'redirect_url' => 'https://all-the.rest/thankyou.html'
        ],
        // Fallback profile if origin is not matching or direct access
        'default' => [
            'from_email' => 'no-reply@reisinger.pictures',
            'from_name' => 'General Website Form',
            'redirect_url' => 'https://reisinger.pictures/thankyou.html'
        ]
    ],

    // --- Mailer Settings ---
    // Choose your mailer: 'native' or 'phpmailer'.
    // 'native' uses the built-in PHP mail() function. It requires no further config but is often unreliable.
    // 'phpmailer' uses the PHPMailer library for reliable email delivery via SMTP.
    'mailer_type' => 'native',

    // --- PHPMailer Specific Options (only used if mailer_type is 'phpmailer') ---
    'mailer_options' => [
        // Choose authentication type: 'password' or 'oauth2'.
        'auth_type' => 'password',

        'host' => 'smtp.example.com',
        'port' => 587, // 587 for TLS, 465 for SSL
        'encryption' => 'tls', // 'tls' or 'ssl'
        'username' => 'your-smtp-username@example.com', // For both password and oauth2
        'from_email' => 'no-reply@example.com', // The address emails will be sent from
        'from_name' => 'Your Website Form',

        // --- Used for 'password' auth_type ---
        'password' => 'your-smtp-password',

        // --- Used for 'oauth2' auth_type (with Google) ---
        // See README.md for instructions on how to get these credentials.
        'oauth' => [
            'clientId' => 'your-google-api-client-id.apps.googleusercontent.com',
            'clientSecret' => 'your-google-api-client-secret',
            'refreshToken' => 'your-google-refresh-token',
        ],
    ],
];