<?php
// Prevent direct access
if (!defined('ACCESS')) {
    die('Direct access not permitted.');
}

// Include Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

/**
 * Sends an email using PHPMailer with SMTP (Password or XOAUTH2).
 *
 * @param array $config The application configuration array.
 * @param string $subject The email subject.
 * @param string $message The email body.
 * @param string $replyToEmail The email address for the Reply-To header.
 * @return bool True on success, false on failure.
 */
function send_email_phpmailer(array $config, string $subject, string $message, string $replyToEmail): bool
{
    $mail = new PHPMailer(true);
    $mailerConfig = $config['mailer_options'];

    try {
        // Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output for troubleshooting
        $mail->isSMTP();
        $mail->Host       = $mailerConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Port       = $mailerConfig['port'];
        $mail->SMTPSecure = $mailerConfig['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

        // Authentication logic
        if ($mailerConfig['auth_type'] === 'oauth2') {
            $mail->AuthType = 'XOAUTH2';
            $provider = new Google([
                'clientId'     => $mailerConfig['oauth']['clientId'],
                'clientSecret' => $mailerConfig['oauth']['clientSecret'],
            ]);
            $mail->setOAuth(new OAuth([
                'provider'     => $provider,
                'clientId'     => $mailerConfig['oauth']['clientId'],
                'clientSecret' => $mailerConfig['oauth']['clientSecret'],
                'refreshToken' => $mailerConfig['oauth']['refreshToken'],
                'userName'     => $mailerConfig['username'],
            ]));
        } else { // Default to 'password'
            $mail->Username   = $mailerConfig['username'];
            $mail->Password   = $mailerConfig['password'];
        }

        // Recipients
        $mail->setFrom($mailerConfig['from_email'], $mailerConfig['from_name']);
        $mail->addAddress($config['receiver_email']);
        $mail->addReplyTo($replyToEmail);

        // Content
        $mail->isHTML(false); // Set to true if you want to send HTML email
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = $message; // For non-HTML mail clients

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error for debugging purposes
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
