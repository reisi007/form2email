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