<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecureFormActionsTest extends TestCase
{
    public function test_login_form_submits_to_https_behind_a_trusted_reverse_proxy(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $response = $this->get('/login', ['X-Forwarded-Proto' => 'https']);

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('action="https://localhost/login"', $content);
        $this->assertStringContainsString('name="_token"', $content);
    }

    public function test_resend_otp_form_submits_to_https_behind_a_trusted_reverse_proxy(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $user = $this->createTeacher(['pending_email' => 'newaddress@example.com']);

        $response = $this->actingAs($user)->get(route('settings.edit'), [
            'X-Forwarded-Proto' => 'https',
        ]);

        $response->assertOk();

        $content = $response->getContent();

        preg_match_all('/action="([^"]*)"/', $content, $matches);

        $this->assertNotEmpty($matches[1]);
        $this->assertContains('https://localhost/account/email-change/resend', $matches[1]);

        foreach ($matches[1] as $action) {
            $this->assertStringStartsWith('https://', $action, "Form action [{$action}] must use HTTPS.");
        }

        $this->assertStringContainsString('name="_token"', $content);
    }

    public function test_verification_resend_form_submits_to_https_behind_a_trusted_reverse_proxy(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $response = $this->get(route('verification.notice', ['email' => 'unverified@example.com']), [
            'X-Forwarded-Proto' => 'https',
        ]);

        $response->assertOk();

        $this->assertStringContainsString(
            'action="https://localhost/email/verify/resend"',
            $response->getContent()
        );
    }

    public function test_proxy_headers_are_ignored_when_running_without_a_trusted_proxy(): void
    {
        // Local development (and this test environment) does not trust any
        // proxy by default, so a spoofed X-Forwarded-Proto header must not
        // change the generated scheme.
        $this->assertNull(config('trustedproxy.proxies'));

        $response = $this->get('/login', ['X-Forwarded-Proto' => 'https']);

        $response->assertOk();

        $this->assertStringContainsString(
            'action="http://localhost/login"',
            $response->getContent()
        );
    }
}
