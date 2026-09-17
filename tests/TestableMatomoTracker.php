<?php

declare(strict_types=1);

namespace MatomoTrackingApi\Tests;

require_once __DIR__ . '/../vendor/matomo/matomo-php-tracker/MatomoTracker.php';

/**
 * Minimal facade over MatomoTracker in order to prevent HTTP requests from
 * being made.
 */
final class TestableMatomoTracker extends \MatomoTracker
{
    public ?string $pageTitle = null;

    public function doTrackPageView(string $pageTitle): string|bool
    {
        $this->pageTitle = $pageTitle;
        return true;
    }
}

