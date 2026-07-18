# Deployment Guide (Portainer)

This document describes how to deploy the `form2email` application and its isolated token-checker sidecar using Portainer.

## Architecture

We use an **Infrastructure as Code (IaC)** approach. The `docker-compose.yml` file is stored in this Git repository. Portainer should pull the stack configuration directly from this repository to ensure a Single Source of Truth.

The stack consists of two services:
1. `app`: The live PHP web application serving the form.
2. `token-checker`: An isolated Alpine Linux container that proactively monitors the validity of the Google OAuth2 token and alerts the administrator via Make.com if it expires.

## Deployment Steps

1. **Log into Portainer** and navigate to your environment.
2. Go to **Stacks** and click **Add stack**.
3. Enter a name for your stack (e.g., `form2email-production`).
4. Select the **Repository** build method (Recommended) or copy-paste the contents of `docker-compose.yml` into the Web editor.
    * *If using Repository:* Enter your Git repository URL. Specify the branch and the path to the `docker-compose.yml` file.
5. Scroll down to **Environment variables**.
6. Click **Add environment variable** and add the following keys with your secure values. **Never commit these values to Git!**

### Required Environment Variables

The `mailer_options.auth_type` setting in `config.php` selects the active mailer
strategy: `password` (SMTP/Basic Auth) or `oauth2` (Google XOAUTH2). Provide the
environment variables that correspond to the chosen strategy.

#### Common (always required)

| Variable Name | Description | Example / Format |
| :--- | :--- | :--- |
| `MAKE_WEBHOOK_URL` | The URL provided by your Make.com Webhook trigger. | `https://hook.eu1.make.com/...` |
| `MAKE_API_KEY` | Your Make.com authentication header key. | `VUzRWb8yWw-JFTc` |

#### Required when `auth_type=oauth2` (Google XOAUTH2)

| Variable Name | Description | Example / Format |
| :--- | :--- | :--- |
| `OAUTH_CLIENT_ID` | Google OAuth2 Client ID. | `...apps.googleusercontent.com` |
| `OAUTH_CLIENT_SECRET` | Google OAuth2 Client Secret. | `GOCSPX-...` |
| `OAUTH_REFRESH_TOKEN` | Google OAuth2 Refresh Token. | `1//03a6...` |

#### Required when `auth_type=password` (SMTP/Basic Auth)

| Variable Name | Description | Example / Format |
| :--- | :--- | :--- |
| `SMTP_PASSWORD` | SMTP password for the configured username. | `your-smtp-password` |
| `SMTP_HOST` | SMTP server hostname (optional, has sample default). | `smtp.example.com` |
| `SMTP_PORT` | SMTP server port (optional, defaults to `587`). | `587` |
| `SMTP_ENCRYPTION` | Encryption mode: `tls` or `ssl` (optional, defaults to `tls`). | `tls` |
| `SMTP_USERNAME` | SMTP username (optional, falls back to config.php). | `contact@example.com` |

> **Note:** When switching from `oauth2` to `password`, the `token-checker`
> sidecar container no longer serves a purpose and can be disabled in the stack
> without affecting the live `app` container.

7. Toggle **Enable relative path volumes** (if applicable to your Portainer setup) to ensure the volume binds work correctly.
8. Click **Deploy the stack**.

## Updating the Stack

When you make changes to the `docker-compose.yml` in this repository:
1. Go to the Stack in Portainer.
2. Click on the **Editor** tab.
3. Click **Pull and update MAC** (or manually click "Update the stack" if you used the Web editor method).