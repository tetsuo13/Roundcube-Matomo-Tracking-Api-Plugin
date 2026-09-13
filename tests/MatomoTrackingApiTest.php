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
        $this->rcmail->config->set('matomo_tracking_api_token_auth', null);
        $this->rcmail->config->set('matomo_tracking_api_track_user_id', false);

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

    public function testConfigurationTrackingUrlIsConfigured(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 1,
            self::CONFIG_VAR_URL => 'example.com'
        ]);

        $tracker = new TestableMatomoTracker(1);
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

        $tracker = new TestableMatomoTracker(1);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('', TestableMatomoTracker::$URL);
        $this->assertCount(1, $this->rcmailErrors);
        $this->assertStringContainsString('tracking URL', $this->rcmailErrors[0]['message']);
    }

    public function testConfigurationTestScalarSiteId(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 1,
            self::CONFIG_VAR_URL => 'example.com'
        ]);

        $tracker = new TestableMatomoTracker(2);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame(2, $tracker->idSite);
        $this->assertEmpty($this->rcmailErrors);
    }

    public function testConfigurationServerSpecificSiteId(): void
    {
        $plugin = $this->setupPlugin([
            self::CONFIG_VAR_SITE_ID => 2,
            self::CONFIG_VAR_URL => 'example.com'
        ]);

        $method = new \ReflectionMethod(\matomo_tracking_api::class, 'getSiteId');
        $method->setAccessible(true);

        $tracker = new TestableMatomoTracker(2);
        $plugin->setTracker($tracker);

        $plugin->init();

        $siteId = $method->invoke($plugin, $this->rcmail);
        $this->assertSame(2, $siteId);
        $this->assertEmpty($this->rcmailErrors);
    }
}

