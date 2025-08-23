# form2mail-php

This is a flexible PHP script to process a form from a static website and send its contents as an email. It is designed to be simple, secure, and configurable.

It supports two methods for sending email:
1.  **Native:** Uses the built-in PHP `mail()` function. Simple and has no dependencies, but requires a properly configured mail server on the host system and is often unreliable on shared hosting.
2.  **PHPMailer:** Uses the popular PHPMailer library to send email via SMTP, which is far more reliable. It supports both standard password authentication and Google XOAUTH2 for enhanced security.

## Requirements
- PHP 8.0+
- Composer _(optional, only required for the `phpmailer` feature)_

## Installation

1.  Clone or download the script to your server.
2.  *This step is optional and only required if you plan to use the `phpmailer` feature:* navigate to the script's directory in your terminal and run:
    ```bash
    composer install
    ```
    This will download the required libraries into a `vendor/` directory. If you only use the `'native'` mailer, you can skip this step entirely.

## Configuration

1.  Rename `config.sample.php` to `config.php`.
2.  Edit `config.php` to set up your parameters.

### General Settings

-   `receiver_email`: The email address where you want to receive the form submissions.
-   `redirect_url`: The URL where the user will be redirected after a successful submission.
-   `email_subject`: The subject line for the emails you will receive.
-   `honeypot_value`: A secret value for a hidden form field to prevent spam. This must match the value in your HTML form.
-   `whitelist`: An array of form field `name` attributes that are allowed to be processed. Any fields not in this list will be rejected. This is a security measure.

### Mailer Configuration

-   **`mailer_type`**: Choose your sending method. If this option is omitted, it will default to `'native'`.
    -   `'native'`: Uses the basic PHP `mail()` function.
    -   `'phpmailer'`: Uses PHPMailer for SMTP. Requires the `mailer_options` to be configured and Composer dependencies to be installed.

### PHPMailer Options

These are only required if `mailer_type` is set to `'phpmailer'`.

-   **`auth_type`**: Choose your authentication type. If this option is set to anything other than `'oauth2'`, it will default to using `'password'`.
    -   `'password'`: Use standard username/password SMTP authentication.
    -   `'oauth2'`: Use Google's XOAUTH2 for more secure authentication.
-   `host`: Your SMTP server address (e.g., `smtp.gmail.com`).
-   `port`: The SMTP port (e.g., `587` for TLS, `465` for SSL).
-   `encryption`: `tls` or `ssl`.
-   `username`: The email address you are authenticating with.
-   `from_email`: The email address the message will be sent from.
-   `from_name`: The name associated with the `from_email`.
-   `password`: Your SMTP password (if using `'password'` auth).
-   `oauth`: Your Google API credentials (if using `'oauth2'` auth).

### Generating a (Google) OAuth Refresh Token (for `auth_type = 'oauth2'`)

If you choose to use Google OAuth2, you need to generate a `refreshToken`. A helper script, `/vendor/phpmailer/phpmailer/get_oauth_token.php`, is provided by PHPMailer for this one-time task.

1.  Copy the file to the root directory
2.  Follow the instruction on https://github.com/PHPMailer/PHPMailer/wiki/Using-Gmail-with-XOAUTH2 (or the specific instruction for your email provider)
3.  **CRITICAL: Delete `get_oauth_token.php` from your server immediately afterwards.**

## HTML Form Example

Your HTML form should `POST` to the `index.php` file.

### Anti-Spam Measures

This script relies on two simple but effective anti-spam techniques that you should implement in your form:

1.  **Honeypot Field:** This is a hidden `input` field that a real user will not see or fill out. Bots, however, will often fill it in. The script checks that this field has the exact `honeypot_value` you set in the config.
2.  **Spam Trap Checkbox:** This is a hidden `checkbox` with a common name (like `terms` or `subscribe`). Bots will often check every box they find. However, this field's name is **intentionally left out of the `whitelist`** in the config. If a bot checks the box, the field is sent with the form, the script sees an un-whitelisted field, and the submission is rejected. A normal user will not see or check the box, so the field is never sent.

### Sample Code

```html
<form method="post" action="[https://your-domain.com/path/to/form2mail/](https://your-domain.com/path/to/form2mail/)">

    <!-- Anti-Spam: Honeypot Field -->
    <input type="hidden" name="honeypot" value="a-unique-and-secret-value"/>

    <!-- Anti-Spam: Spam Trap Checkbox. Do NOT add "terms" to the whitelist. -->
    <div style="display: none;">
        <label>Keep this box unchecked</label>
        <input type="checkbox" name="terms" value="1" tabindex="-1">
    </div>

    <!-- Optional: Add a prefix to the email subject -->
    <input type="hidden" name="subject_prefix" value="Urgent">

    <!-- Your form fields (must be in the whitelist) -->
    <div>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div>
        <label for="message">Message:</label>
        <textarea id="message" name="message" required></textarea>
    </div>

    <button type="submit">Send Message</button>
</form>
