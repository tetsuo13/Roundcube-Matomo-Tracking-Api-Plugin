<?php

declare(strict_types=1);

namespace MatomoTrackingApi\Tests;

require_once __DIR__ . '/../vendor/matomo/matomo-php-tracker/MatomoTracker.php';

final class TestableMatomoTracker extends \MatomoTracker
{
    public ?string $pageTitle = null;

    public function doTrackPageView($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }
}

