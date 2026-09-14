<?php

declare(strict_types=1);

namespace MatomoTrackingApi\Tests;

require_once __DIR__ . '/../vendor/matomo/matomo-php-tracker/MatomoTracker.php';

final class TestableMatomoTracker extends \MatomoTracker
{
    public ?string $pageTitle = null;
    public ?string $trackedUrl = null;

    public function doTrackPageView(string $pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    public function setUrl(string $url): self
    {
        $this->trackedUrl = $url;
        return parent::setUrl($url);
    }
}

