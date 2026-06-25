<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use Softspring\CmsBundle\Model\SiteInterface;

use function rawurlencode;
use function rtrim;

class PlausibleStatisticsProvider implements StatisticsProviderInterface
{
    public function __construct(
        private readonly PlausibleConfigurationResolver $configurationResolver,
        private readonly PlausibleStatsClient $statsClient,
    ) {
    }

    public function getName(): string
    {
        return 'plausible';
    }

    public function resolveConfiguration(SiteInterface $site, ?string $path = null): StatisticsConfiguration
    {
        $configuration = $this->configurationResolver->resolve($site);
        $dashboardUrl = null;

        if (null !== $path) {
            $dashboardUrl = $configuration->dashboardUrl($path);
        } elseif ($configuration->isUsable()) {
            $dashboardUrl = rtrim($configuration->apiBaseUrl, '/').'/'.rawurlencode($configuration->siteId);
        }

        return new StatisticsConfiguration(
            provider: $this->getName(),
            enabled: $configuration->enabled,
            usable: $configuration->isUsable(),
            missingReasons: $configuration->missingReasons(),
            dashboardUrl: $dashboardUrl,
            context: [
                'apiBaseUrl' => $configuration->apiBaseUrl,
                'siteId' => $configuration->siteId,
            ],
        );
    }

    public function getSiteMetrics(SiteInterface $site, string $dateRange, ?string $path = null): array
    {
        $configuration = $this->configurationResolver->resolve($site);

        return null === $path
            ? $this->statsClient->getSiteMetrics($configuration, $dateRange)
            : $this->statsClient->getPageMetrics($configuration, $path, $dateRange);
    }

    public function queryPages(SiteInterface $site, PageStatisticsQuery $query): array
    {
        return $this->statsClient->queryPages($this->configurationResolver->resolve($site), $query);
    }
}
