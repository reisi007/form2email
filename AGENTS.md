# AI Agent Guidelines (AGENTS.md)

This document contains repository-specific context, architectural decisions, and rules for AI agents (and human developers) working on this project.

## 1. Agent Behavior & Documentation Strategy
* **Proactive Rule Suggestion:** As an AI agent, if you identify a recurring issue, a missing security best practice, or a structural improvement, proactively suggest adding a new rule to this `AGENTS.md` file.
* **Language Requirements:** All code, inline code comments, and documentation (like this file) MUST be written in English. Any text or instructions directed at the end user (e.g., chat responses) should be in German.
* **Complete Code Output:** When proposing code changes or updating a file, ALWAYS output the entire, complete code of that file. Do not use snippets, diffs, or omit sections with comments like `// ... existing code ...`. This strict requirement prevents copy-paste errors and ensures the user always has a fully functional file to deploy.
* **Code Documentation (PHPDoc):** Never remove or truncate existing PHPDoc comments. All methods and functions MUST be documented correctly using standard PHPDoc blocks, including detailed descriptions, `@param` types/descriptions, and `@return` types/descriptions.

## 2. Environment & Security (Deployment)
* **Environment Variables (ENV):** Never hardcode sensitive data (like API tokens, SMTP passwords, OAuth keys, or Webhook URLs) in the code or in `config.php`. Always use `getenv('VARIABLE_NAME')` in PHP. These values must be injected via the environment (e.g., via Portainer stack variables).
* **Portainer Stack Updates:** When modifying the `docker-compose.yml`, ensure that environment variables are passed using the `${VARIABLE_NAME}` syntax so they can be centrally managed in the Portainer UI.

## 3. Architecture & Monitoring
* **Separation of Concerns (Health Checks):** Validating external connections (such as checking if a Google OAuth token is valid) must NEVER be done within the live web application code. This polling logic must be placed in a separate, isolated sidecar container within the `docker-compose.yml`.
* **Proactive Error Alerting & Data Fallback:** Any critical failure—whether it is a failed email transmission in the live app (`index.php`) or an expired token detected by the sidecar checker—must trigger an external webhook (defined via `MAKE_WEBHOOK_URL` and `MAKE_API_KEY`) to notify the administrator via Make.com. In the event of a live app failure, the webhook payload MUST include the raw form data so no user inquiries are lost.

## 4. Infrastructure as Code (IaC)
* **Single Source of Truth:** The `docker-compose.yml` file MUST be version-controlled and stored in this repository. Portainer deployments should pull this file directly from the repository. This prevents configuration drift and ensures that the repository remains the single source of truth for both application logic and infrastructure deployment.

## 5. Test & Failover Discipline
* **Automated Tests for Security-Critical Code:** All security-critical or pure helper functions (whitelist enforcement, redirect-origin validation, secret/ENV resolution, etc. — currently located in `src/functions.php`) MUST be covered by PHPUnit tests in `tests/`. New helpers in that surface area MUST ship with tests in the same change. The suite is executed via `composer test` (or `./vendor/bin/phpunit`) and MUST pass before a change is merged.
* **Failover Validation After Mailer Changes:** Any change to `mailer_phpmailer.php`, `mailer_native.php`, `mailer.php`, or the catch/exception flow in `index.php` MUST be followed by a manual forced-failure test (e.g., temporarily set an invalid `SMTP_PASSWORD` or stop the SMTP target) to prove the Make.com webhook fires and the form-data payload is delivered. This guards against silent regressions of the failure handler (such as catching the wrong `Exception` class).

## 6. Sync Workflow (Deployment)
* **Mandatory Sync Order:** When the user requests a "sync" (deploying the current state to the live server via `./sync.sh`), the following order is REQUIRED:
  1. **Run the tests first.** Execute `composer test` (or `./vendor/bin/phpunit`). Do not proceed if the suite is failing.
  2. **Commit and push any open changes** before syncing. Do not sync with uncommitted/unpushed work left behind.
  3. **Only then run the sync** (`./sync.sh`).
  Rationale: a sync deploys to the live server; uncommitted work or broken tests must never reach production silently.