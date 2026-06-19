# CMS Analytics Plugin (Experimental)

[![Latest Stable](https://img.shields.io/packagist/v/softspring/cms-analytics-plugin?label=stable&style=flat-square)](https://github.com/softspring/cms-analytics-plugin/releases)
[![Latest Unstable](https://img.shields.io/packagist/v/softspring/cms-analytics-plugin?label=unstable&style=flat-square&include_prereleases)](https://github.com/softspring/cms-analytics-plugin/releases)
[![License](https://img.shields.io/packagist/l/softspring/cms-analytics-plugin?style=flat-square)](https://github.com/softspring/cms-analytics-plugin/blob/6.0/LICENSE)
[![PHP Version](https://img.shields.io/packagist/dependency-v/softspring/cms-analytics-plugin/php?style=flat-square)](https://github.com/softspring/cms-analytics-plugin/blob/6.0/composer.json)
[![Downloads](https://img.shields.io/packagist/dt/softspring/cms-analytics-plugin?style=flat-square)](https://packagist.org/packages/softspring/cms-analytics-plugin)
[![CI](https://img.shields.io/github/actions/workflow/status/softspring/cms-analytics-plugin/ci.yml?branch=6.0&style=flat-square&label=CI)](https://github.com/softspring/cms-analytics-plugin/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/softspring/cms-analytics-plugin?branch=6.0&style=flat-square)](https://app.codecov.io/gh/softspring/cms-analytics-plugin/tree/6.0)

> **Experimental package:** this plugin is in active development and its configuration, analytics provider integration, UI, and extension points may change before a stable release.

`softspring/cms-analytics-plugin` adds page-level analytics to Armonic CMS administration.

It resolves the configured URLs for a CMS content item, queries Plausible Analytics for each URL, and shows totals and per-URL metrics in the content administration screen.

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

Each CMS site reads Plausible configuration from `site.extra.analytics.plausible`:

```yaml
site:
    extra:
        analytics:
            plausible:
                enabled: true
                api_base_url: '%env(PLAUSIBLE_API_BASE_URL)%'
                api_key: '%env(PLAUSIBLE_STATS_API_KEY)%'
                site_id: '%env(PLAUSIBLE_SITE_ID)%'
```

Use a Plausible Stats API key. Do not use or share personal account credentials for integration tests.
Set real keys in local or deployment secrets, not in committed files.

The default Plausible API base URL is `https://plausible.io`. Override `api_base_url` only for self-hosted Plausible installations or compatible endpoints.

Google Analytics integration is planned for a future version.

## Usage

The plugin adds a `Statistics` tab to every CMS content type. It queries Plausible once per configured URL of the content
and caches each response for 15 minutes through Symfony cache.

Supported ranges are:

- Last 7 days.
- Last 30 days.
- Last 91 days.
- Last 6 months.
- Last 12 months.
- This year.

## Extension Points

Replace `Softspring\CmsAnalyticsPlugin\Analytics\PlausibleConfigurationResolver` when a project stores analytics settings outside CMS site configuration.

Replace `Softspring\CmsAnalyticsPlugin\Analytics\PlausibleStatsClient` when a project needs a different analytics provider, cache policy, or Plausible query shape.

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
