Piwik Tracking Plugin for Roundcube
===================================

This plugin integrates Piwik analytics using the
[Piwik Tracking API](https://piwik.org/docs/tracking-api/) into Roundcube. This
plugin is very different from the
[Roundcube Webmail piwik analytics plugin](http://blog.no-panic.at/projects/roundcube-webmail-piwik-analytics-plugin/)
which adds the client-side
[JavaScript Tracking Tag](https://piwik.org/docs/javascript-tracking/). The aim
of this plugin is to add Piwik integration on the server-side in order to get
around same-origin policy.

If your Piwik installation is on the same domain as your Roundcube
installation and both are using the same protocol, then this plugin is
probably not what you need as it will not add any significant value over the
JavaScript Tracking Tag. Some example cases where this plugin shines:

* Piwik at `http://analytics.company.com`, Roundcube at
  `https://webmail.company.com`
* Piwik at `http://analytics.company.com`, Roundcube at
  `http://webmail.othercompany.com`

## Install

Install using Composer or manually download and install into
`plugins/piwik_tracking_api`. Copy `config.inc.php.dist` to `config.inc.php`
in the same directory and edit the file using the options shown below.

Add piwik_tracking_api to `$config['plugins']` in your Roundcube config to
enable the plugin.

## Configuration

Copy `config.inc.php.dist` to `config.inc.php` and edit the configuration
variables. Set optional variables to `null` when unused.

**piwik_tracking_api_url** [string]

Set this to the URL of the Piwik installation. This URL must be accessible
from the Roundcube installation.

**piwik_tracking_api_site_id** [int|array(string => int)]

Configures the Piwik site ID. The value of this variable can either be a
single integer or an array containing multiple server names and IDs in cases
where a single Roundcube installation serves multiple hosts.

To set multiple hosts, use the key/value pair of server name and Piwik website
ID. For example:

```php
$rcmail_config['piwik_tracking_api_site_id'] = array(
    'webmail.foo.com' => 42,
    'webmail.bar.com' => 13
);
```

**piwik_tracking_api_token_auth** [string] _(Optional)_

Set to the token auth key of a Piwik user in order to take advantage of
advanced tracking. Currently utilizes the following if provided:

* Sets remote IP to that of the user instead of defaulting to the IP of the
  Roundcube installation.

**piwik_tracking_api_user_email_custom_var** [int] _(Optional)_

Custom variable slot ID to record the email of the logged in user. If used,
this should be a number from 1 to 5.

