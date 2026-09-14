<?php

declare(strict_types=1);

namespace MatomoTrackingApi\Tests;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/roundcube/roundcubemail/program/include/iniset.php';
require_once __DIR__ . '/TestableMatomoTracker.php';
require_once __DIR__ . '/../matomo_tracking_api.php';

use PHPUnit\Framework\TestCase;

final class MatomoTrackingApiTest extends TestCase
{
    private const CONFIG_VAR_SITE_ID = 'matomo_tracking_api_site_id';
    private const CONFIG_VAR_URL = 'matomo_tracking_api_url';
    private const CONFIG_VAR_TOKEN_AUTH = 'matomo_tracking_api_token_auth';
    private const CONFIG_VAR_TRACK_USER_ID = 'matomo_tracking_api_track_user_id';

    /**
     * Captures any errors raised by the plugin.
     */
    private array $rcmailErrors = [];

    private ?\rcmail $rcmail = null;

    protected function setUp(): void
    {
        $this->rcmail = \rcmail::get_instance(0, 'test');

        $this->rcmail->config->set('devel_mode', false);

        // Reset static instance variables between tests.
        $this->rcmail->config->set(self::CONFIG_VAR_URL, null);
        $this->rcmail->config->set(self::CONFIG_VAR_SITE_ID, null);
        $this->rcmail->config->set(self::CONFIG_VAR_TOKEN_AUTH, null);
        $this->rcmail->config->set(self::CONFIG_VAR_TRACK_USER_ID, false);

        TestableMatomoTracker::$URL = '';

        // Capture any errors raised by the plugin.
        $this->rcmail->plugins->register_hook('raise_error', function ($args) use (&$error) {
            $this->rcmailErrors[] = $args;
            return $args;
        });

        $_SERVER = [
            'SERVER_NAME' => 'https://example.com',
            'REQUEST_URI' => '/'
        ];
    }

    protected function tearDown(): void
    {
        $_SERVER = [];

        parent::tearDown();
    }

    public function testConstructor(): void
    {
        $plugin = $this->setupPlugin();

        $this->assertInstanceOf(\matomo_tracking_api::class, $plugin);
        $this->assertInstanceOf(\rcube_plugin::class, $plugin);
        $this->assertEmpty($this->rcmailErrors);
    }

    private function setupPlugin(array $config = []): \matomo_tracking_api
    {
        $rcube = \rcmail::get_instance();

        foreach ($config as $k => $v) {
            $rcube->config->set($k, $v);
        }

        return new \matomo_tracking_api($rcube->plugins);
    }

    private function createTracker(int $siteId): TestableMatomoTracker
    {
        return new TestableMatomoTracker($siteId);
    }

    public function testConfigurationTrackingUrlIsConfigured(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 1,
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(1);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('example.com', TestableMatomoTracker::$URL);
        $this->assertEmpty($this->rcmailErrors);
    }

    public function testConfigurationMissingTrackingUrlRaisesError(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 1,
        ]);

        $tracker = $this->createTracker(1);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('', TestableMatomoTracker::$URL);
        $this->assertCount(1, $this->rcmailErrors);
        $this->assertSame(2, $this->rcmailErrors[0]['code']);
        $this->assertStringContainsString(
            'tracking URL',
            $this->rcmailErrors[0]['message']
        );
    }

    public function testConfigurationScalarSiteId(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(42, $tracker->idSite);
        $this->assertEmpty($this->rcmailErrors);
    }

    public function testConfigurationServerSpecificSiteId(): void
    {
        $_SERVER['SERVER_NAME'] = 'test.example.com';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => [
                'test.example.com' => 42,
                'foo.example.com' => 81,
            ],
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(42, $tracker->idSite);
        $this->assertEmpty($this->rcmailErrors);
    }

    public function testConfigurationServerSpecificSiteIdSelectsCorrectServer(): void
    {
        $_SERVER['SERVER_NAME'] = 'foo.example.com';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => [
                'test.example.com' => 42,
                'foo.example.com' => 81,
            ],
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(81);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(81, $tracker->idSite);
        $this->assertEmpty($this->rcmailErrors);
    }

    public function testConfigurationMissingSiteIdRaisesError(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(1);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('', TestableMatomoTracker::$URL);
        $this->assertCount(1, $this->rcmailErrors);
        $this->assertSame(3, $this->rcmailErrors[0]['code']);
        $this->assertStringContainsString(
            'site ID',
            $this->rcmailErrors[0]['message']
        );
    }

    public function testConfigurationUnknownServerRaisesError(): void
    {
        $_SERVER['SERVER_NAME'] = 'unknown.example.com';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => [
                'test.example.com' => 42,
                'foo.example.com' => 81,
            ],
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(1);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertCount(1, $this->rcmailErrors);
        $this->assertSame(4, $this->rcmailErrors[0]['code']);
        $this->assertStringContainsString(
            'unknown.example.com',
            $this->rcmailErrors[0]['message']
        );
    }

    public function testTrackingPageTitle(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('', $tracker->pageTitle);
    }

    public function testTrackingUrlUsesHttp(): void
    {
        $_SERVER['SERVER_NAME'] = 'webmail.example.com';
        $_SERVER['REQUEST_URI'] = '/?_task=mail&_action=list';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'https://matomo.example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(
            'http://webmail.example.com/?_task=mail&_action=list',
            $tracker->trackedUrl
        );
    }

    public function testTrackingUrlUsesHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'webmail.example.com';
        $_SERVER['REQUEST_URI'] = '/?_task=mail&_action=list';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'https://matomo.example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(
            'https://webmail.example.com/?_task=mail&_action=list',
            $tracker->trackedUrl
        );
    }

    public function testTrackingUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Test User Agent';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('Test User Agent', $tracker->userAgent);
    }

    public function testTrackingWithoutUserAgent(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertFalse($tracker->userAgent);
    }

    public function testTrackingReferer(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/inbox';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(
            'https://example.com/inbox',
            $tracker->urlReferrer
        );
    }

    public function testTrackingWithoutReferer(): void
    {
        unset($_SERVER['HTTP_REFERER']);

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertFalse($tracker->urlReferrer);
    }

    public function testTrackingTokenAuthentication(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
            self::CONFIG_VAR_TOKEN_AUTH => 'test-token',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('test-token', $tracker->token_auth);
    }

    public function testTrackingWithoutTokenAuthentication(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertFalse($tracker->token_auth);
    }

    public function testTrackingIpWithTokenAuthentication(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.0.2.123';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
            self::CONFIG_VAR_TRACK_USER_ID => 'test-token',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('192.0.2.123', $tracker->ip);
    }

    public function testTrackingSetsIpWithoutTokenAuthentication(): void
    {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertFalse($tracker->token_auth);
        $this->assertSame('1.2.3.4', $tracker->ip);
    }
}

