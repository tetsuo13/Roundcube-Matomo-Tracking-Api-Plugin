# Roundcube Matomo Tracking API Changelog

## [Unreleased](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/2.0.0...main)

- Renamed from Piwik to Matomo.
- Removed bundled matomo-php-tracker in favor of using Composer to handle dependencies.
- Addressed many of the PHP deprecated warnings that were being logged ([#11](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/issues/11))
- Silently abort if server vars used to determine Roundcube installation URL are missing ([#11](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/issues/11))
- Changed default 600s request timeout down to 5s ([#18](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/issues/18))
- Removed unnecessary `matomo_tracking_api_token_auth` config used only for tracking remote IP, it's tracked automatically ([#17](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/issues/17))

## [2.0.0](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/1.1.0...2.0.0) (2016-12-06)

## [1.1.0](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/1.0.5...1.1.0) (2016-11-18)

## [1.0.5](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/rc1.0.0-piwik1-1.4...1.0.5) (2016-05-03)

## [rc1.0.0-piwik1-1.4](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/rc1.0.0-piwik1-1.3...rc1.0.0-piwik1-1.4) (2015-10-27)

## [rc1.0.0-piwik1-1.3](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/rc1.0.0-piwik1-1.2...rc1.0.0-piwik1-1.3) (2015-10-27)

## [rc1.0.0-piwik1-1.2](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/rc0.9.5-piwik1-1.2...rc1.0.0-piwik1-1.2) (2014-07-07)

## [rc0.9.5-piwik1-1.2](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/rc1.0.0-piwik1-1.1...rc0.9.5-piwik1-1.2) (2014-04-15)

## [rc1.0.0-piwik1-1.1](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/rc1.0.0-piwik1-1.0...rc1.0.0-piwik1-1.1) (2014-04-15)

## [rc1.0.0-piwik1-1.0](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/rc0.9.5-piwik1-1.1...rc1.0.0-piwik1-1.0) (2014-04-09)

## [rc0.9.5-piwik1-1.1](https://github.com/tetsuo13/Roundcube-Matomo-Tracking-Api-Plugin/compare/rc0.9.5-piwik1-1.0...rc0.9.5-piwik1-1.1) (2013-11-06)

## rc0.9.5-piwik1-1.0 (2013-11-05)

Initial release

