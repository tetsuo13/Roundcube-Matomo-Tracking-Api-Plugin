<?php

declare(strict_types=1);

namespace MatomoTrackingApi\Tests;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/roundcube/roundcubemail/program/include/iniset.php';
require_once __DIR__ . '/TestableMatomoTracker.php';
require_once __DIR__ . '/../matomo_tracking_api.php';

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class MatomoTrackingApiTest extends TestCase
{
    private const CONFIG_VAR_SITE_ID = 'matomo_tracking_api_site_id';
    private const CONFIG_VAR_URL = 'matomo_tracking_api_url';
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
            self::CONFIG_VAR_URL => 'example.com'
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
            self::CONFIG_VAR_SITE_ID => 1
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
            self::CONFIG_VAR_URL => 'example.com'
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
                'foo.example.com' => 81
            ],
            self::CONFIG_VAR_URL => 'example.com'
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
                'foo.example.com' => 81
            ],
            self::CONFIG_VAR_URL => 'example.com'
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
            self::CONFIG_VAR_URL => 'example.com'
        ]);

        $tracker = $this->createTracker(1);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('', TestableMatomoTracker::$URL);
        $this->assertNull($tracker->pageTitle);
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
                'foo.example.com' => 81
            ],
            self::CONFIG_VAR_URL => 'example.com'
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
            self::CONFIG_VAR_URL => 'example.com'
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
            self::CONFIG_VAR_URL => 'https://matomo.example.com'
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(
            'http://webmail.example.com/?_task=mail&_action=list',
            $tracker->pageUrl
        );
    }

    public function testTrackingUrlUsesHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'webmail.example.com';
        $_SERVER['REQUEST_URI'] = '/?_task=mail&_action=list';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'https://matomo.example.com'
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(
            'https://webmail.example.com/?_task=mail&_action=list',
            $tracker->pageUrl
        );
    }

    public function testTrackingUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = __FUNCTION__;

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com'
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(__FUNCTION__, $tracker->userAgent);
    }

    public function testTrackingWithoutUserAgent(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com'
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertNull($tracker->userAgent);
    }

    public function testTrackingReferer(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/inbox';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com'
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
            self::CONFIG_VAR_URL => 'example.com'
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertNull($tracker->urlReferrer);
    }

    public function testTrackingWithoutTokenAuthentication(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com'
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertNull($tracker->token_auth);
    }

    private function setupRoundcubeWebmailMock(
        ?string $userEmail,
        bool $trackUserIdEnabled
    ): \rcmail {
        $rcmail = $this->createMock(\rcmail::class);

        $times = 1;

        if ($userEmail === null || $trackUserIdEnabled === false) {
            $times = 0;
        }

        $rcmail->expects($this->exactly($times))
            ->method('get_user_email')
            ->willReturn($userEmail);

        $config = $this->createMock(\rcube_config::class);

        $config->method('get')
            ->willReturnMap([
                [self::CONFIG_VAR_URL, null, 'example.com'],
                [self::CONFIG_VAR_SITE_ID, null, 42],
                [self::CONFIG_VAR_TRACK_USER_ID, false, $trackUserIdEnabled]
            ]);

        $rcmail->config = $config;

        return $rcmail;
    }

    public function testTrackingUserIdWithTokenAuthentication(): void
    {
        $expectedUserId = 'foo@example.com';

        $rcmail = $this->setupRoundcubeWebmailMock($expectedUserId, true);
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
            self::CONFIG_VAR_TRACK_USER_ID => true
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);
        $plugin->setRoundcubeWebmail($rcmail);

        $plugin->init();

        $this->assertSame($expectedUserId, $tracker->userId);
    }

    public function testNotTrackingUserIdWithTokenAuthenticationWhenNull(): void
    {
        $rcmail = $this->setupRoundcubeWebmailMock(null, true);
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
            self::CONFIG_VAR_TRACK_USER_ID => true
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);
        $plugin->setRoundcubeWebmail(null);

        $plugin->init();

        $this->assertNull($tracker->userId);
    }

    public function testNotTrackingUserIdWithoutTokenAuthentication(): void
    {
        $rcmail = $this->setupRoundcubeWebmailMock('foo@example.com', false);
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com',
            self::CONFIG_VAR_TRACK_USER_ID => true
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);
        $plugin->setRoundcubeWebmail(null);

        $plugin->init();

        $this->assertNull($tracker->userId);
    }

    public function testTrackingSetsIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';

        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42,
            self::CONFIG_VAR_URL => 'example.com'
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertNull($tracker->token_auth);
        $this->assertSame('1.2.3.4', $tracker->ip);
    }

    #[TestWith([null, '/?_task=mail'])]
    #[TestWith(['webmail.example.com', null])]
    #[TestWith([null, null])]
    public function testInitAbortsWhenServerNameOrRequestUriMissing(
        ?string $serverName,
        ?string $requestUri
    ): void {
        if ($serverName === null) {
            unset($_SERVER['SERVER_NAME']);
        } else {
            $_SERVER['SERVER_NAME'] = $serverName;
        }

        if ($requestUri === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $requestUri;
        }

        // Don't supply tracking URL.
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 42
        ]);

        $tracker = $this->createTracker(42);
        $plugin->setTracker($tracker);

        $plugin->init();

        // Should never get to verifying the tracking URL is set.
        $this->assertEmpty($this->rcmailErrors);
    }
}

