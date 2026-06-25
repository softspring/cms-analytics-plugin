# CMS Analytics Plugin Features

Functional definition for `softspring/cms-analytics-plugin`.

This package extends Armonic CMS with page-level analytics in content administration screens.

## Purpose

- Help editors inspect traffic for published CMS URLs from the CMS admin.
- Connect CMS content URLs with external analytics data.
- Keep analytics credentials in site configuration and deployment secrets.
- Provide a provider-agnostic statistics API for CMS admin and MCP consumers.

## Main Features

- Statistics tab for CMS content administration screens.
- Statistics provider chain for resolving the active analytics provider for each CMS site.
- Public statistics API for aggregate site metrics and sortable page queries.
- Per-content URL discovery from CMS routes, route paths, locales, and sites.
- Plausible Stats API integration through Symfony HttpClient.
- Site-specific Plausible configuration under `site.extra.analytics.plausible`.
- Google Analytics 4 Data API integration through Symfony HttpClient.
- Site-specific GA4 configuration under `site.extra.analytics.google_analytics_4`.
- Site-specific analytics driver selection under `site.extra.analytics.driver`.
- Response caching through Symfony cache contracts.
- Aggregated metric cards for pageviews, visitors, visits, views per visit, bounce rate, and time on page.
- Per-URL status reporting when analytics is not configured or the external API returns an error.

## Expected Usage

- Install it in a Symfony project that already uses `softspring/cms-bundle`.
- Register `SfsCmsAnalyticsPlugin` as a Symfony bundle when Flex does not do it automatically.
- Configure the analytics driver and provider credentials per CMS site using secrets or environment variables.
- Use the CMS content statistics tab to inspect analytics for each configured URL.

## Extension Points

- Add analytics providers by implementing `StatisticsProviderInterface` and tagging them with `sfs_cms_analytics.statistics_provider`.
- Replace `PlausibleConfigurationResolver` when Plausible settings live outside CMS site configuration.
- Replace `PlausibleStatsClient` when a project needs a different Plausible cache policy or query shape.
- Replace `GoogleAnalyticsConfigurationResolver`, `GoogleAnalyticsAccessTokenProvider`, or `GoogleAnalyticsDataClient` when GA4 settings, authentication, or query shape must be customized.
- Override Twig templates through Symfony template resolution.
- Override menu or routing provider services when a project needs custom admin placement.

## Current Limits

- The package ships Plausible and Google Analytics 4 providers; GA4 authentication requires the optional `google/auth`
  package and project-specific providers can be added through the statistics provider chain.
- Metrics are fetched on demand from the admin UI and cached for 15 minutes.
- Visitor totals are summed across URLs, so the same visitor can be counted more than once when they viewed several URLs.
- The package does not ship frontend assets.
