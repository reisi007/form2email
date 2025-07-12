<?php
// Prevent direct access
if (!defined('ACCESS')) {
    die('Direct access not permitted.');
}

return [
    'receiver_email' => 'your-email@example.com',
    'redirect_url' => 'thankyou.html',
    'email_subject' => 'New Form Submission',
    'honeypot_value' => 'secret_key', // Expected value for honeypot field
	'whitelist' => ['email', 'honeypot', 'subject_prefix'] // Case-insensitive whitelist of allowed fields (email and honeypot are required, subject_prefix is defined, but optional)
];
