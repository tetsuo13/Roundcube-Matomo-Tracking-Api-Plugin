<?php

/**
 * Piwki Tracking API.
 *
 * Adds the PHP Piwki tracking API.
 *
 * @version rc1.0.0-piwik1-1.0
 * @author  Andrei Nicholson
 * @url     https://github.com/tetsuo13/Roundcube-Piwik-Tracking-Api-Plugin
 */
class piwik_tracking_api extends rcube_plugin
{
    /**
     * Entry point for plugin. Track on all events.
     */
    public function init()
    {
        require_once dirname(__FILE__) . '/PiwikTracker.php';

        $rcmail = rcmail::get_instance();

        $this->load_config();

        $trackingUrl = $this->getTrackingUrl($rcmail);

        if ($trackingUrl === false) {
            return;
        }

        PiwikTracker::$URL = $trackingUrl;

        $siteId = $this->getSiteId($rcmail);

        if ($siteId === false) {
            return;
        }

        $tracker = new PiwikTracker($siteId);

        $tokenAuth = $rcmail->config->get('piwik_tracking_api_token_auth', null);

        if ($tokenAuth !== null) {
            $tracker->setTokenAuth($tokenAuth);
        }

        if ($this->gset('HTTP_USER_AGENT')) {
            $tracker->setUserAgent($_SERVER['HTTP_USER_AGENT']);
        }

        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://')
             . $_SERVER['SERVER_NAME']
             . $_SERVER['REQUEST_URI'];

        $tracker->setUrl($url);

        if ($this->gset('HTTP_REFERER')) {
            $tracker->setUrlReferer($_SERVER['HTTP_REFERER']);
        }

        if ($tokenAuth !== null && $this->gset('REMOTE_ADDR')) {
            $tracker->setIp($_SERVER['REMOTE_ADDR']);
        }

        $tracker->doTrackPageView();
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
     * Get the required Piwik tracking URL from config.
     *
     * @param rcube $rcmail Roundcube object.
     * @return string Tracking URL.
     */
    private function getTrackingUrl(rcube $rcmail)
    {
        $trackingUrl = $rcmail->config->get('piwik_tracking_api_url', null);

        if ($trackingUrl === null) {
            rcmail::raise_error(
                array(
                    'code' => 2,
                    'type' => 'php',
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'message' => 'tracking URL is required for the piwik_tracking_api plugin'
                ),
                true,
                false
            );
            return false;
        }

        return $trackingUrl;
    }

    /**
     * Get the required Piwik site ID from config.
     *
     * @param rcmail $rcmail Roundcube object.
     * @return int Site ID.
     */
    private function getSiteId(rcmail $rcmail)
    {
        $siteId = $rcmail->config->get('piwik_tracking_api_site_id', null);

        if ($siteId === null) {
            rcmail::raise_error(
                array(
                    'code' => 3,
                    'type' => 'php',
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'message' => 'site ID required for the piwik_tracking_api plugin'
                ),
                true,
                false
            );
            return false;
        }

        if (!is_array($siteId)) {
            return $siteId;
        }

        if (isset($siteId[$_SERVER['SERVER_NAME']])) {
            return $siteId[$_SERVER['SERVER_NAME']];
        }

        rcmail::raise_error(
            array(
                'code' => 4,
                'type' => 'php',
                'file' => __FILE__,
                'line' => __LINE__,
                'message' => 'unable to find ' . $_SERVER['SERVER_NAME'] . ' in site ID array for piwik_tracking_api plugin'
            ),
            true,
            false
        );

        return false;
    }
}

