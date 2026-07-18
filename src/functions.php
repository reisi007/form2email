<?php
/**
 * Shared pure helper functions for the Form2Email application.
 *
 * This file is autoloaded via composer.json ("autoload.files") so that both the
 * live web entry point (index.php) and the PHPMailer integration
 * (mailer_phpmailer.php) as well as the test suite can reuse the same building
 * blocks without triggering request execution.
 *
 * Each function in this file MUST remain free of side effects (no I/O, no
 * superglobal mutation) so it can be exercised by unit tests.
 */

declare(strict_types=1);

/**
 * Checks if all provided fields are in a whitelist (case-insensitive).
 *
 * @param array $fields    The array of fields to check (e.g., $_POST).
 * @param array $whitelist The array of allowed field names.
 * @return bool True if all fields are whitelisted, false otherwise.
 */
function areFieldsWhitelisted(array $fields, array $whitelist): bool
{
    $lowercaseWhitelist = array_map('strtolower', $whitelist);
    foreach (array_keys($fields) as $field) {
        if (!in_array(strtolower($field), $lowercaseWhitelist, true)) {
            return false;
        }
    }
    return true;
}

/**
 * Safely extracts and normalizes the origin from a given URL to prevent open
 * redirect vulnerabilities. It strictly filters against protocol-relative paths
 * and malformed slash combinations.
 *
 * @param string $url The URL to parse and validate.
 * @return string The normalized origin (scheme://host[:port]) or an empty string if invalid.
 */
function getOriginFromUrl(string $url): string
{
    // Block protocol-relative URLs (e.g., //attacker.com) and backslashes to prevent parser bypasses
    if (str_starts_with($url, '//') || str_contains($url, '\\')) {
        return '';
    }

    $parsed = parse_url($url);
    if (!$parsed || empty($parsed['host']) || empty($parsed['scheme'])) {
        return '';
    }

    // Enforce web-safe protocols only
    $scheme = strtolower($parsed['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return '';
    }

    $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
    return $scheme . '://' . strtolower($parsed['host']) . $port;
}

/**
 * Reads a configuration value with an environment variable fallback.
 *
 * This helper centralises the rule from AGENTS.md §2: secrets and other
 * deployment-specific values must never be hardcoded in config.php. If the
 * value is present in the config array it is preferred; otherwise the
 * matching environment variable is read via getenv().
 *
 * @param array  $configArray The configuration sub-array to look in (e.g., $mailerConfig or $oauth).
 * @param string $configKey   The key to look up inside $configArray.
 * @param string $envName     The environment variable name used as a fallback.
 * @return string|null The resolved value, or null if neither source provides one.
 */
function resolveMailerSecret(array $configArray, string $configKey, string $envName): ?string
{
    if (!empty($configArray[$configKey])) {
        return (string)$configArray[$configKey];
    }
    $envValue = getenv($envName);
    return $envValue === false ? null : (string)$envValue;
}
