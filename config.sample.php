<?php
// Prevent direct access
if (!defined('ACCESS')) {
    die('Direct access not permitted.');
}

return [
    // --- General Settings ---
    // Each value can be overridden by an environment variable (recommended for
    // production deployments, see AGENTS.md §2). The fallback after "?: " is
    // used only when the variable is not present.
    'receiver_email' => getenv('RECEIVER_EMAIL') ?: 'contact@example.com',
    'email_subject' => getenv('EMAIL_SUBJECT') ?: 'New message from contact form',
    'honeypot_value' => getenv('HONEYPOT_VALUE') ?: '00000000-0000-0000-0000-000000000000',

    // Case-insensitive whitelist of allowed form fields.
    // 'email' and 'honeypot' are required. '_next' is optional and, when present,
    // is strictly validated against the active profile's origin (see index.php).
    // 'subject', 'subject_prefix' and 'phone' are optional payload fields.
    'whitelist' => ['email', 'name', 'message', 'phone', 'honeypot', 'subject', 'subject_prefix', '_next'],

    // --- CORS & Domain Routing Settings ---
    // List of allowed origins for CORS headers and redirect validation.
    'allowed_origins' => [
        'https://example.com',
        'https://forms.example.com',
        'http://localhost:4321', // Allows local testing (B2C profile)
        'http://localhost:4322'  // Allows local testing (B2B profile)
    ],

    // Dynamic profiles based on the HTTP_ORIGIN.
    // This allows routing to different sender aliases, receiving addresses, and
    // redirect URLs per origin. The 'redirect_url' acts as the post-submit default
    // and can be overridden per-request by the '_next' form field (validated
    // against the active profile's origin, see index.php).
    'domain_profiles' => [
        'https://example.com' => [
            'from_email' => 'contact@example.com',
            'from_name' => 'Contact form - example.com',
            'receiver_email' => 'contact@example.com',
            'redirect_url' => 'https://example.com/thank-you'
        ],
        'https://forms.example.com' => [
            'from_email' => 'forms@example.com',
            'from_name' => 'Contact form - forms.example.com',
            'receiver_email' => 'forms@example.com',
            'redirect_url' => 'https://forms.example.com/thank-you'
        ],
        // Fallback profile if origin is not matching or direct access.
        // Example of an environment-driven redirect URL (recommended).
        'default' => [
            'from_email' => 'no-reply@example.com',
            'from_name' => 'Contact form',
            'redirect_url' => getenv('DEFAULT_REDIRECT_URL') ?: 'https://example.com/thank-you'
        ]
    ],

    // --- Mailer Settings ---
    // Choose your mailer: 'native' or 'phpmailer'.
    // 'native' uses the built-in PHP mail() function. It requires no further config but is often unreliable.
    // 'phpmailer' uses the PHPMailer library for reliable email delivery via SMTP.
    'mailer_type' => 'native',

    // --- PHPMailer Specific Options (only used if mailer_type is 'phpmailer') ---
    'mailer_options' => [
        // Choose authentication type: 'password' (SMTP/Basic Auth) or 'oauth2' (Google XOAUTH2).
        // Both strategies are supported and selectable here. The corresponding
        // secrets MUST be injected via environment variables (see deployment/DEPLOYMENT.md).
        'auth_type' => 'password',

        'host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
        'port' => (int)(getenv('SMTP_PORT') ?: 587), // 587 for TLS, 465 for SSL
        'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls', // 'tls' or 'ssl'
        'username' => getenv('SMTP_USERNAME') ?: 'contact@example.com', // SMTP user for both auth types
        'from_email' => getenv('FROM_EMAIL') ?: 'no-reply@example.com', // The address emails will be sent from
        'from_name' => getenv('FROM_NAME') ?: 'Your Website Form',

        // --- Used for 'password' auth_type ---
        // Read from the SMTP_PASSWORD environment variable; never hardcode in this file.
        'password' => getenv('SMTP_PASSWORD') ?: '',

        // --- Used for 'oauth2' auth_type (with Google) ---
        // Read from the OAUTH_* environment variables; never hardcode in this file.
        // See README.md for instructions on how to obtain these credentials.
        'oauth' => [
            'clientId' => getenv('OAUTH_CLIENT_ID') ?: '',
            'clientSecret' => getenv('OAUTH_CLIENT_SECRET') ?: '',
            'refreshToken' => getenv('OAUTH_REFRESH_TOKEN') ?: '',
        ],
    ],
];
