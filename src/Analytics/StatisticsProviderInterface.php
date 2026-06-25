<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use Softspring\CmsBundle\Model\SiteInterface;

interface StatisticsProviderInterface
{
    public function getName(): string;

    public function resolveConfiguration(SiteInterface $site, ?string $path = null): StatisticsConfiguration;

    /**
     * @return array<string, int|float|null>
     */
    public function getSiteMetrics(SiteInterface $site, string $dateRange, ?string $path = null): array;

    /**
     * @return list<array{path: string, metrics: array<string, int|float|null>}>
     */
    public function queryPages(SiteInterface $site, PageStatisticsQuery $query): array;
}
