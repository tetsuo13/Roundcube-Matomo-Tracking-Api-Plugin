<?php

require_once __DIR__ . '/vendor/matomo/matomo-php-tracker/MatomoTracker.php';

/**
 * Matomo tracking API plugin for Roundcube webmail.
 *
 * Adds the PHP Matomo tracking API to all server-side requests.
 */
class matomo_tracking_api extends rcube_plugin
{
    private const PLUGIN_VERSION = '2.0.0';

    private $tracker = null;
    private $rcmail = null;

    /**
     * Provide information about this plugin.
     *
     * @return array Meta information about plugin.
     */
    public static function info()
    {
        return [
            'name'    => 'Matomo Tracking',
            'vendor'  => 'Andrei Nicholson',
            'version' => self::PLUGIN_VERSION,
            'license' => 'GPL-3.0-or-later',
            'uri'     => 'https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin'
        ];
    }

    /**
     * Call prior to {@see init()} to inject a custom tracker. Intended for
     * unit tests. Tracker must have same API as {@see MatomoTracker}.
     *
     * @param MatomoTracker $tracker Custom MatomoTracker instance.
     */
    public function setTracker($tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * Call prior to {@see init()} to inject a custom rcmail instance.
     * Intended for unit tests.
     *
     * @param rcmail $rcmail Roundcube webmail instance.
     */
    public function setRoundcubeWebmail($rcmail)
    {
        $this->rcmail = $rcmail;
    }

    /**
     * Entry point for plugin. Track on all events.
     */
    public function init()
    {
        // Abort if can't determine the URL. It's not guaranteed that PHP will
        // supply these server vars.
        if (!isset($_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'])) {
            return;
        }

        if ($this->rcmail === null) {
            $this->rcmail = rcmail::get_instance();
        }

        $this->load_config();

        $trackingUrl = $this->getTrackingUrl();

        if ($trackingUrl === false) {
            return;
        }

        $siteId = $this->getSiteId();

        if ($siteId === false) {
            return;
        }

        if ($this->tracker === null) {
            $this->tracker = new MatomoTracker($siteId);
        }

        // Done this roundabout way instead of `MatomoTracker::$URL` because
        // unit tests may inject test stubs.
        $trackerClass = get_class($this->tracker);
        $trackerClass::$URL = $trackingUrl;

        $tokenAuth = $this->rcmail->config->get('matomo_tracking_api_token_auth', null);

        if ($tokenAuth !== null) {
            $this->tracker->setTokenAuth($tokenAuth);
        }

        $trackUserId = $this->rcmail->config->get('matomo_tracking_api_track_user_id', false);

        if ($trackUserId === true) {
            // Unauthenticated users will return null.
            $userEmail = $this->rcmail->get_user_email();

            if ($userEmail !== null) {
                $this->tracker->setUserId($userEmail);
            }
        }

        if ($this->gset('HTTP_USER_AGENT')) {
            $this->tracker->setUserAgent($_SERVER['HTTP_USER_AGENT']);
        }

        $url = ($this->gset('HTTPS') && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://')
             . $_SERVER['SERVER_NAME']
             . $_SERVER['REQUEST_URI'];
        $trackingUrl = $this->trimUrlForTracking($url);
        $this->tracker->setUrl($trackingUrl);

        if ($this->gset('HTTP_REFERER')) {
            $this->tracker->setUrlReferer($this->trimUrlForTracking($_SERVER['HTTP_REFERER']));
        }

        if ($tokenAuth !== null && $this->gset('REMOTE_ADDR')) {
            $this->tracker->setIp($_SERVER['REMOTE_ADDR']);
        }

        $this->tracker->doTrackPageView('');
    }

    /**
     * Remove any Personally Identifiable Information (PII) from the URL.
     *
     * @param string
     * @return string
     */
    private static function trimUrlForTracking($url)
    {
        if ($url === '') {
            return $url;
        }

        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);

        $safe = array_intersect_key($query, array_flip(['_task', '_action']));

        $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path']
             . ($safe ? '?' . http_build_query($safe) : '');

        return $url;
    }

    /**
     * Check for superglobal _SERVER key.
     *
     * @param string $i Key to search for.
     * @return bool True if it exists.
     */
    private function gset($i)
    {
        return isset($_SERVER[$i]);
    }

    /**
     * Get the required Matomo tracking URL from config.
     *
     * @return string|bool Tracking URL.
     */
    private function getTrackingUrl()
    {
        $trackingUrl = $this->rcmail->config->get('matomo_tracking_api_url', null);

        if ($trackingUrl === null) {
            rcmail::raise_error('Missing tracking URL config var matomo_tracking_api_url', true);
            return false;
        }

        return $trackingUrl;
    }

    /**
     * Get the required Matomo site ID from config.
     *
     * @return int|bool Site ID.
     */
    private function getSiteId()
    {
        $siteId = $this->rcmail->config->get('matomo_tracking_api_site_id', null);

        if ($siteId === null) {
            rcmail::raise_error('Missing site ID config var matomo_tracking_api_site_id', true);
            return false;
        }

        if (!is_array($siteId)) {
            return $siteId;
        }

        if (isset($siteId[$_SERVER['SERVER_NAME']])) {
            return $siteId[$_SERVER['SERVER_NAME']];
        }

        rcmail::raise_error(
            'Unable to find ' . $_SERVER['SERVER_NAME'] . ' in site ID config array matomo_tracking_api_site_id',
            true
        );

        return false;
    }
}

