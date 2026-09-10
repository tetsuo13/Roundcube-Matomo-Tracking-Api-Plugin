<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/roundcube/roundcubemail/program/lib/Roundcube/bootstrap.php';
require_once __DIR__ . '/../vendor/roundcube/roundcubemail/program/lib/Roundcube/rcube_plugin_api.php';
require_once __DIR__ . '/../vendor/roundcube/roundcubemail/program/lib/Roundcube/rcube_plugin.php';
require_once __DIR__ . '/../matomo_tracking_api.php';

use PHPUnit\Framework\TestCase;

final class MatomoTrackingApiTest extends TestCase
{
    public function testConstructor(): void
    {
        $rcube = \rcube::get_instance();
        $plugin = new \matomo_tracking_api($rcube->plugins);

        $this->assertInstanceOf('matomo_tracking_api', $plugin);
        $this->assertInstanceOf('rcube_plugin', $plugin);
    }
}

