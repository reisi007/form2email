<?php
// Prevent direct access
if (!defined('ACCESS')) {
    die('Direct access not permitted.');
}

return [
    'receiver_email' => 'your-email@example.com',
    'redirect_url' => 'thankyou.html',
    'email_subject' => 'New Form Submission'
];
