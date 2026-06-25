<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;
use Softspring\CmsAnalyticsPlugin\Analytics\ContentAnalyticsProvider;
use Softspring\CmsAnalyticsPlugin\Analytics\PageUrl;
use Softspring\CmsAnalyticsPlugin\Analytics\PageUrlResolver;
use Softspring\CmsAnalyticsPlugin\Analytics\StatisticsConfiguration;
use Softspring\CmsAnalyticsPlugin\Analytics\StatisticsProviderChain;
use Softspring\CmsAnalyticsPlugin\Analytics\StatisticsProviderInterface;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\RoutePathInterface;
use Softspring\CmsBundle\Model\SiteInterface;

class ContentAnalyticsProviderTest extends TestCase
{
    public function testItBuildsRowsAndTotalsOnlyFetchingUsableConfigurations(): void
    {
        $content = $this->createMock(ContentInterface::class);
        $site = $this->createMock(SiteInterface::class);
        $site->method('getConfig')->willReturn([
            'extra' => [
                'analytics' => [
                    'driver' => 'plausible',
                ],
            ],
        ]);
        $routePath = $this->createMock(RoutePathInterface::class);
        $pageUrl = new PageUrl($site, $routePath, '/en/services', 'https://example.org/en/services', 'en');

        $pageUrlResolver = $this->createMock(PageUrlResolver::class);
        $pageUrlResolver->method('resolve')->with($content)->willReturn([$pageUrl]);

        $configuration = new StatisticsConfiguration('plausible', true, true);
        $statisticsProvider = $this->createMock(StatisticsProviderInterface::class);
        $statisticsProvider->method('getName')->willReturn('plausible');
        $statisticsProvider->expects($this->once())
            ->method('resolveConfiguration')
            ->with($site, '/en/services')
            ->willReturn($configuration);
        $statisticsProvider->expects($this->once())
            ->method('getSiteMetrics')
            ->with($site, '30d', '/en/services')
            ->willReturn([
                'visitors' => 12,
                'visits' => 14,
                'pageviews' => 25,
                'views_per_visit' => 1.8,
                'bounce_rate' => 32.5,
                'time_on_page' => 44,
            ]);

        $provider = new ContentAnalyticsProvider($pageUrlResolver, new StatisticsProviderChain([$statisticsProvider]));

        $rows = $provider->getRows($content, '30d');

        $this->assertCount(1, $rows);
        $this->assertSame($pageUrl, $rows[0]['pageUrl']);
        $this->assertSame($configuration, $rows[0]['configuration']);
        $this->assertNull($rows[0]['error']);
        $this->assertSame(25, $rows[0]['metrics']['pageviews']);

        $this->assertSame([
            'visitors' => 12.0,
            'visits' => 14.0,
            'pageviews' => 25.0,
            'views_per_visit' => 1.8,
            'bounce_rate' => 32.5,
            'time_on_page' => 44.0,
        ], $provider->buildTotals($rows));
    }
}
