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

| Variable Name | Description | Example / Format |
| :--- | :--- | :--- |
| `MAKE_WEBHOOK_URL` | The URL provided by your Make.com Webhook trigger. | `https://hook.eu1.make.com/...` |
| `MAKE_API_KEY` | Your Make.com authentication header key. | `VUzRWb8yWw-JFTc` |
| `OAUTH_CLIENT_ID` | Google OAuth2 Client ID. | `...apps.googleusercontent.com` |
| `OAUTH_CLIENT_SECRET` | Google OAuth2 Client Secret. | `GOCSPX-...` |
| `OAUTH_REFRESH_TOKEN` | Google OAuth2 Refresh Token. | `1//03a6...` |

7. Toggle **Enable relative path volumes** (if applicable to your Portainer setup) to ensure the volume binds work correctly.
8. Click **Deploy the stack**.

## Updating the Stack

When you make changes to the `docker-compose.yml` in this repository:
1. Go to the Stack in Portainer.
2. Click on the **Editor** tab.
3. Click **Pull and update MAC** (or manually click "Update the stack" if you used the Web editor method).