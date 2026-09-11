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
    /**
     * Captures any errors raised by the plugin.
     */
    private array $rcmailErrors = [];

    protected function setUp(): void
    {
        $rcmail = \rcmail::get_instance(0, 'test');

        $rcmail->config->set('devel_mode', false);

        // Reset static instance variables between tests.
        $rcmail->config->set('matomo_tracking_api_url', null);
        $rcmail->config->set('matomo_tracking_api_site_id', null);
        $rcmail->config->set('matomo_tracking_api_token_auth', null);
        $rcmail->config->set('matomo_tracking_api_track_user_id', false);

        TestableMatomoTracker::$URL = '';

        // Capture any errors raised by the plugin.
        $rcmail->plugins->register_hook('raise_error', function ($args) use (&$error) {
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
            'matomo_tracking_api_site_id' => 1,
            'matomo_tracking_api_url' => 'example.com'
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
            'matomo_tracking_api_site_id' => 1
        ]);

        $tracker = new TestableMatomoTracker(1);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('', TestableMatomoTracker::$URL);
        $this->assertCount(1, $this->rcmailErrors);
        $this->assertStringContainsString('tracking URL', $this->rcmailErrors[0]['message']);
    }

    public function testTracksPageView(): void
    {
        $plugin = $this->setupPlugin([
            'matomo_tracking_api_site_id' => 1,
            'matomo_tracking_api_url' => 'example.com'
        ]);

        $tracker = new TestableMatomoTracker(1);
        $plugin->setTracker($tracker);

        $plugin->init();

        $this->assertSame('', $tracker->pageTitle);
        $this->assertEmpty($this->rcmailErrors);
    }
}

