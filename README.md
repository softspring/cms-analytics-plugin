# CMS Analytics Plugin (Experimental)

[![Latest Stable](https://img.shields.io/packagist/v/softspring/cms-analytics-plugin?label=stable&style=flat-square)](https://github.com/softspring/cms-analytics-plugin/releases)
[![Latest Unstable](https://img.shields.io/packagist/v/softspring/cms-analytics-plugin?label=unstable&style=flat-square&include_prereleases)](https://github.com/softspring/cms-analytics-plugin/releases)
[![License](https://img.shields.io/packagist/l/softspring/cms-analytics-plugin?style=flat-square)](https://github.com/softspring/cms-analytics-plugin/blob/6.0/LICENSE)
[![PHP Version](https://img.shields.io/packagist/dependency-v/softspring/cms-analytics-plugin/php?style=flat-square)](https://github.com/softspring/cms-analytics-plugin/blob/6.0/composer.json)
[![Downloads](https://img.shields.io/packagist/dt/softspring/cms-analytics-plugin?style=flat-square)](https://packagist.org/packages/softspring/cms-analytics-plugin)
[![CI](https://img.shields.io/github/actions/workflow/status/softspring/cms-analytics-plugin/ci.yml?branch=6.0&style=flat-square&label=CI)](https://github.com/softspring/cms-analytics-plugin/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/softspring/cms-analytics-plugin?branch=6.0&style=flat-square)](https://app.codecov.io/gh/softspring/cms-analytics-plugin/tree/6.0)

> **Experimental package:** this plugin is in active development and its configuration, analytics provider integration, UI, and extension points may change before a stable release.

`softspring/cms-analytics-plugin` adds page-level analytics to Armonic CMS administration and exposes a provider-agnostic statistics API for other CMS plugins.

It resolves the configured URLs for a CMS content item, queries the configured statistics provider for each URL, and shows totals and per-URL metrics in the content administration screen. The plugin ships a Plausible provider; projects can add GA4 or other providers behind the same statistics API.

## Installation

```bash
composer require softspring/cms-analytics-plugin:^6.0@dev
```

The plugin requires `softspring/cms-bundle`, Symfony HttpClient, and a cache service.

Register the bundle if Symfony Flex does not do it automatically:

```php
// config/bundles.php
return [
    Softspring\CmsAnalyticsPlugin\SfsCmsAnalyticsPlugin::class => ['all' => true],
];
```

## Configuration

Each CMS site reads provider configuration from `site.extra.analytics`.

Select the active provider with `site.extra.analytics.driver`. When no driver is set, the plugin uses the Plausible
provider as the default.

Plausible configuration lives under `site.extra.analytics.plausible`:

```yaml
site:
    extra:
        analytics:
            driver: plausible
            plausible:
                enabled: true
                api_base_url: '%env(PLAUSIBLE_API_BASE_URL)%'
                api_key: '%env(PLAUSIBLE_STATS_API_KEY)%'
                site_id: '%env(PLAUSIBLE_SITE_ID)%'
```

Use a Plausible Stats API key. Do not use or share personal account credentials for integration tests.
Set real keys in local or deployment secrets, not in committed files.

The default Plausible API base URL is `https://plausible.io`. Override `api_base_url` only for self-hosted Plausible installations or compatible endpoints.

Google Analytics 4 configuration lives under `site.extra.analytics.google_analytics_4`:

```yaml
site:
    extra:
        analytics:
            driver: google_analytics_4
            google_analytics_4:
                enabled: true
                property_id: '%env(GA4_PROPERTY_ID)%'
                credentials_json: '%env(GA4_CREDENTIALS_JSON)%'
                credentials_path: '%env(GA4_CREDENTIALS_PATH)%'
                dashboard_base_url: 'https://analytics.google.com/analytics/web'
```

`property_id` is the numeric GA4 property id. GA4 support needs the optional `google/auth` package:

```bash
composer require google/auth
```

Use either `credentials_json`, `credentials_path`, or neither. When both credential fields are empty, Google application
default credentials are used. The selected identity must have read access to the GA4 property.

## Usage

The plugin adds a `Statistics` tab to every CMS content type. It queries the statistics provider once per configured URL of the content
and caches each response for 15 minutes through Symfony cache.

Supported ranges are:

- Last 7 days.
- Last 30 days.
- Last 91 days.
- Last 6 months.
- Last 12 months.
- This year.

## Extension Points

Add custom analytics providers by implementing `Softspring\CmsAnalyticsPlugin\Analytics\StatisticsProviderInterface` and tagging the service with `sfs_cms_analytics.statistics_provider`.

Replace `Softspring\CmsAnalyticsPlugin\Analytics\PlausibleConfigurationResolver` when a project stores Plausible settings outside CMS site configuration.

Replace `Softspring\CmsAnalyticsPlugin\Analytics\PlausibleStatsClient` when a project needs a different Plausible cache policy or query shape.

Replace `Softspring\CmsAnalyticsPlugin\Analytics\GoogleAnalyticsConfigurationResolver`, `Softspring\CmsAnalyticsPlugin\Analytics\GoogleAnalyticsAccessTokenProvider`, or `Softspring\CmsAnalyticsPlugin\Analytics\GoogleAnalyticsDataClient` when a project needs custom GA4 configuration, authentication, or query behavior.

Override Twig templates through Symfony template resolution when the admin screen needs project-specific layout or metrics.

## Features

See [FEATURES.md](FEATURES.md) for the functional scope of this package.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

[Report issues](https://github.com/softspring/cms-analytics-plugin/issues) and [send Pull Requests](https://github.com/softspring/cms-analytics-plugin/pulls)

## Security

See [SECURITY.md](SECURITY.md).

## License

This package is free and released under the [AGPL-3.0 license](LICENSE).
