<?php

declare(strict_types=1);

namespace Form2Email\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the shared pure helpers declared in src/functions.php.
 *
 * These functions are the security-critical surface of the application
 * (whitelist enforcement, open-redirect mitigation, secret resolution) and
 * MUST be covered by automated tests to guard against regressions.
 */
final class FunctionsTest extends TestCase
{
    /**
     * Resets the shared environment state after each test so that env-based
     * assertions do not leak across tests.
     */
    protected function tearDown(): void
    {
        putenv('SMTP_PASSWORD');
        putenv('OAUTH_CLIENT_ID');
        putenv('OAUTH_CLIENT_SECRET');
        putenv('OAUTH_REFRESH_TOKEN');
        parent::tearDown();
    }

    // ---------------------------------------------------------------------
    // areFieldsWhitelisted()
    // ---------------------------------------------------------------------

    public function test_areFieldsWhitelisted_returns_true_when_all_fields_allowed(): void
    {
        $fields    = ['email' => 'a@b.com', 'name' => 'John', 'message' => 'hi'];
        $whitelist = ['email', 'name', 'message'];

        $this->assertTrue(areFieldsWhitelisted($fields, $whitelist));
    }

    public function test_areFieldsWhitelisted_is_case_insensitive(): void
    {
        // The application normalises via strtolower(); ensure exact-match is
        // not required (defends against UI / HTML casing drift).
        $fields    = ['EMAIL' => 'a@b.com', 'Name' => 'John'];
        $whitelist = ['email', 'name'];

        $this->assertTrue(areFieldsWhitelisted($fields, $whitelist));
    }

    public function test_areFieldsWhitelisted_rejects_unknown_field(): void
    {
        // This is the whitelist bypass guard: an attacker-supplied extra key
        // (e.g., a forged submit button or hidden injection) MUST be rejected.
        $fields    = ['email' => 'a@b.com', 'role' => 'admin'];
        $whitelist = ['email', 'name', 'message'];

        $this->assertFalse(areFieldsWhitelisted($fields, $whitelist));
    }

    public function test_areFieldsWhitelisted_accepts_empty_input(): void
    {
        $this->assertTrue(areFieldsWhitelisted([], ['email', 'name']));
    }

    // ---------------------------------------------------------------------
    // getOriginFromUrl()
    // ---------------------------------------------------------------------

    /**
     * @return array<string, array{0:string, 1:string}>
     */
    #[DataProvider('provide_valid_origins')]
    public function test_getOriginFromUrl_normalises_valid_urls(string $url, string $expected): void
    {
        $this->assertSame($expected, getOriginFromUrl($url));
    }

    /** @return array<string, array{0:string, 1:string}> */
    public static function provide_valid_origins(): array
    {
        return [
            'plain https'              => ['https://example.com/thank-you', 'https://example.com'],
            'http with path and query' => ['http://example.com/path?x=1', 'http://example.com'],
            'uppercase host'           => ['https://EXAMPLE.COM/Path', 'https://example.com'],
            'explicit port'            => ['https://example.com:8443/x', 'https://example.com:8443'],
        ];
    }

    #[DataProvider('provide_open_redirect_attempts')]
    public function test_getOriginFromUrl_blocks_open_redirect_payloads(string $malicious): void
    {
        // Regression guard for the open-redirect class. None of these payloads
        // may produce an attacker-controlled origin.
        $this->assertSame('', getOriginFromUrl($malicious));
    }

    /** @return array<string, array{0:string}> */
    public static function provide_open_redirect_attempts(): array
    {
        return [
            'protocol-relative'  => ['//attacker.com/path'],
            'backslash bypass'   => ['https:\\\\attacker.com'],
            'javascript scheme'  => ['javascript://attacker.com/%0aalert(1)'],
            'data scheme'        => ['data:text/html,<script>alert(1)</script>'],
            'file scheme'        => ['file:///etc/passwd'],
            'missing host'       => ['https:///path-only'],
            'garbage'            => ['not-a-url'],
            'empty string'       => [''],
        ];
    }

    // ---------------------------------------------------------------------
    // resolveMailerSecret()
    // ---------------------------------------------------------------------

    public function test_resolveMailerSecret_prefers_config_value(): void
    {
        // Config wins over env so local-dev overrides remain possible.
        putenv('SMTP_PASSWORD=env-should-be-ignored');
        $result = resolveMailerSecret(['password' => 'cfg-value'], 'password', 'SMTP_PASSWORD');

        $this->assertSame('cfg-value', $result);
    }

    public function test_resolveMailerSecret_falls_back_to_env(): void
    {
        // Production path: secrets live in env, config.php ships empty strings.
        putenv('SMTP_PASSWORD=env-secret-value');
        $result = resolveMailerSecret(['password' => ''], 'password', 'SMTP_PASSWORD');

        $this->assertSame('env-secret-value', $result);
    }

    public function test_resolveMailerSecret_returns_null_when_neither_source_provides_value(): void
    {
        // The application must be able to detect "no credential configured" and
        // trigger the failure webhook instead of silently sending with empty creds.
        putenv('SMTP_PASSWORD');
        $result = resolveMailerSecret(['password' => ''], 'password', 'SMTP_PASSWORD');

        $this->assertNull($result);
    }

    public function test_resolveMailerSecret_handles_missing_key(): void
    {
        putenv('OAUTH_CLIENT_ID=env-client-id');
        $result = resolveMailerSecret([], 'clientId', 'OAUTH_CLIENT_ID');

        $this->assertSame('env-client-id', $result);
    }
}
