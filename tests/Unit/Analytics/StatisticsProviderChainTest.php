<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;
use Softspring\CmsAnalyticsPlugin\Analytics\PageStatisticsQuery;
use Softspring\CmsAnalyticsPlugin\Analytics\StatisticsConfiguration;
use Softspring\CmsAnalyticsPlugin\Analytics\StatisticsProviderChain;
use Softspring\CmsAnalyticsPlugin\Analytics\StatisticsProviderInterface;
use Softspring\CmsBundle\Model\SiteInterface;

final class StatisticsProviderChainTest extends TestCase
{
    public function testItUsesTheConfiguredSiteDriver(): void
    {
        $site = $this->siteWithAnalytics(['driver' => 'google_analytics_4']);
        $plausible = new TestStatisticsProvider('plausible', true);
        $googleAnalytics = new TestStatisticsProvider('google_analytics_4', false);

        $chain = new StatisticsProviderChain([$plausible, $googleAnalytics]);

        $this->assertSame($googleAnalytics, $chain->getProvider($site));
    }

    public function testItDefaultsToPlausibleWhenNoDriverIsConfigured(): void
    {
        $site = $this->siteWithAnalytics([]);
        $googleAnalytics = new TestStatisticsProvider('google_analytics_4', true);
        $plausible = new TestStatisticsProvider('plausible', true);

        $chain = new StatisticsProviderChain([$googleAnalytics, $plausible]);

        $this->assertSame($plausible, $chain->getProvider($site));
    }

    /**
     * @param array<string, mixed> $analyticsConfig
     */
    private function siteWithAnalytics(array $analyticsConfig): SiteInterface
    {
        $site = $this->createMock(SiteInterface::class);
        $site->method('getConfig')->willReturn([
            'extra' => [
                'analytics' => $analyticsConfig,
            ],
        ]);

        return $site;
    }
}

final readonly class TestStatisticsProvider implements StatisticsProviderInterface
{
    public function __construct(
        private string $name,
        private bool $usable,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function resolveConfiguration(SiteInterface $site, ?string $path = null): StatisticsConfiguration
    {
        return new StatisticsConfiguration(
            provider: $this->name,
            enabled: true,
            usable: $this->usable,
        );
    }

    public function getSiteMetrics(SiteInterface $site, string $dateRange, ?string $path = null): array
    {
        return [];
    }

    public function queryPages(SiteInterface $site, PageStatisticsQuery $query): array
    {
        return [];
    }
}
