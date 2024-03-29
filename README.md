# Matomo Tracking Plugin for Roundcube

[![Continuous integration](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/actions/workflows/ci.yml)
[![Stable Version](https://img.shields.io/packagist/v/tetsuo13/matomo_tracking_api.svg)](https://packagist.org/packages/tetsuo13/matomo_tracking_api)

This plugin integrates Matomo analytics using the
[Matomo Tracking API](https://matomo.org/docs/tracking-api/) into Roundcube.
This plugin is very different from the
[Roundcube Webmail piwik analytics plugin](https://blog.no-panic.at/projects/roundcube-webmail-piwik-analytics-plugin/)
which adds the client-side
[JavaScript Tracking Tag](https://developer.matomo.org/api-reference/tracking-javascript).
The aim of this plugin is to add Matomo integration on the server-side in order
to get around same-origin policy.

If your Matomo installation is on the same domain as your Roundcube
installation and both are using the same protocol, then this plugin is
probably not what you need as it will not add any significant value over the
JavaScript Tracking Tag. Some example cases where this plugin shines:

* Matomo at `http://analytics.company.com`, Roundcube at
  `https://webmail.company.com`
* Matomo at `http://analytics.company.com`, Roundcube at
  `http://webmail.othercompany.com`

## Install

Install using Composer or manually download and install into
`plugins/matomo_tracking_api`. Copy `config.inc.php.dist` to `config.inc.php`
in the same directory and edit the file using the options shown below.

Add matomo_tracking_api to `$config['plugins']` in your Roundcube config to
enable the plugin.

## Configuration

Copy `config.inc.php.dist` to `config.inc.php` and edit the configuration
variables. Set optional variables to `null` when unused.

**matomo_tracking_api_url** [string]

Set this to the URL of the Matomo installation. This URL must be accessible
from the Roundcube installation.

**matomo_tracking_api_site_id** [int|array(string => int)]

Configures the Matomo site ID. The value of this variable can either be a
single integer or an array containing multiple server names and IDs in cases
where a single Roundcube installation serves multiple hosts.

To set multiple hosts, use the key/value pair of server name and Matomo website
ID. For example:

```php
$rcmail_config['matomo_tracking_api_site_id'] = array(
    'webmail.foo.com' => 42,
    'webmail.bar.com' => 13
);
```

**matomo_tracking_api_track_user_id** [boolean]

When enabled, the user's email address will be used to connect multiple
devices and browsers. See
[Benefits of User ID](https://matomo.org/docs/user-id/#benefits-of-enabling-user-id-tracking)
at Matomo's User Guide for more information.

**matomo_tracking_api_token_auth** [string] _(Optional)_

Set to the token auth key of a Matomo user in order to take advantage of
advanced tracking. Currently utilizes the following if provided:

* Sets remote IP to that of the user instead of defaulting to the IP of the
  Roundcube installation.

