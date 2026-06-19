# CMS Analytics Plugin Features

Functional definition for `softspring/cms-analytics-plugin`.

This package extends Armonic CMS with page-level analytics in content administration screens.

## Purpose

- Help editors inspect traffic for published CMS URLs from the CMS admin.
- Connect CMS content URLs with external analytics data.
- Keep analytics credentials in site configuration and deployment secrets.
- Provide a simple integration point for projects that use Plausible Analytics.

## Main Features

- Statistics tab for CMS content administration screens.
- Per-content URL discovery from CMS routes, route paths, locales, and sites.
- Plausible Stats API integration through Symfony HttpClient.
- Site-specific Plausible configuration under `site.extra.analytics.plausible`.
- Response caching through Symfony cache contracts.
- Aggregated metric cards for pageviews, visitors, visits, views per visit, bounce rate, and time on page.
- Per-URL status reporting when analytics is not configured or the external API returns an error.

## Expected Usage

- Install it in a Symfony project that already uses `softspring/cms-bundle`.
- Register `SfsCmsAnalyticsPlugin` as a Symfony bundle when Flex does not do it automatically.
- Configure Plausible credentials per CMS site using secrets or environment variables.
- Use the CMS content statistics tab to inspect analytics for each configured URL.

## Extension Points

- Replace `PlausibleConfigurationResolver` when analytics settings live outside CMS site configuration.
- Replace `PlausibleStatsClient` when a project needs a different analytics provider or query shape.
- Override Twig templates through Symfony template resolution.
- Override menu or routing provider services when a project needs custom admin placement.

## Current Limits

- The package currently supports Plausible Analytics only.
- Google Analytics integration is planned for a future version.
- Metrics are fetched on demand from the admin UI and cached for 15 minutes.
- Visitor totals are summed across URLs, so the same visitor can be counted more than once when they viewed several URLs.
- The package does not ship frontend assets.
