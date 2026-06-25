<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use Softspring\CmsBundle\Model\SiteInterface;

final class GoogleAnalyticsStatisticsProvider implements StatisticsProviderInterface
{
    public function __construct(
        private readonly GoogleAnalyticsConfigurationResolver $configurationResolver,
        private readonly GoogleAnalyticsDataClient $dataClient,
    ) {
    }

    public function getName(): string
    {
        return 'google_analytics_4';
    }

    public function resolveConfiguration(SiteInterface $site, ?string $path = null): StatisticsConfiguration
    {
        $configuration = $this->configurationResolver->resolve($site);

        return new StatisticsConfiguration(
            provider: $this->getName(),
            enabled: $configuration->enabled,
            usable: $configuration->isUsable(),
            missingReasons: $configuration->missingReasons(),
            dashboardUrl: $configuration->dashboardUrl($path),
            context: [
                'propertyId' => $configuration->propertyId,
                'dashboardBaseUrl' => $configuration->dashboardBaseUrl,
            ],
        );
    }

    public function getSiteMetrics(SiteInterface $site, string $dateRange, ?string $path = null): array
    {
        $configuration = $this->configurationResolver->resolve($site);

        return null === $path
            ? $this->dataClient->getSiteMetrics($configuration, $dateRange)
            : $this->dataClient->getPageMetrics($configuration, $path, $dateRange);
    }

    public function queryPages(SiteInterface $site, PageStatisticsQuery $query): array
    {
        return $this->dataClient->queryPages($this->configurationResolver->resolve($site), $query);
    }
}
