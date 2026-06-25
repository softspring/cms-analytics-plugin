<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use Softspring\CmsBundle\Model\SiteInterface;

use function is_scalar;
use function rtrim;
use function trim;

final class GoogleAnalyticsConfigurationResolver
{
    public function resolve(SiteInterface $site): GoogleAnalyticsConfiguration
    {
        $siteConfig = $site->getConfig() ?? [];
        $config = $siteConfig['extra']['analytics']['google_analytics_4'] ?? [];

        return new GoogleAnalyticsConfiguration(
            enabled: (bool) ($config['enabled'] ?? false),
            propertyId: trim((string) ($config['property_id'] ?? '')),
            credentialsJson: $this->normalizeOptionalString($config['credentials_json'] ?? null),
            credentialsPath: $this->normalizeOptionalString($config['credentials_path'] ?? null),
            dashboardBaseUrl: rtrim((string) ($config['dashboard_base_url'] ?? 'https://analytics.google.com/analytics/web'), '/'),
        );
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
