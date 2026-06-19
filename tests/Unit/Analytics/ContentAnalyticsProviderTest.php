<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;
use Softspring\CmsAnalyticsPlugin\Analytics\ContentAnalyticsProvider;
use Softspring\CmsAnalyticsPlugin\Analytics\PageUrl;
use Softspring\CmsAnalyticsPlugin\Analytics\PageUrlResolver;
use Softspring\CmsAnalyticsPlugin\Analytics\PlausibleConfiguration;
use Softspring\CmsAnalyticsPlugin\Analytics\PlausibleConfigurationResolver;
use Softspring\CmsAnalyticsPlugin\Analytics\PlausibleStatsClient;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\RoutePathInterface;
use Softspring\CmsBundle\Model\SiteInterface;

class ContentAnalyticsProviderTest extends TestCase
{
    public function testItBuildsRowsAndTotalsOnlyFetchingUsableConfigurations(): void
    {
        $content = $this->createMock(ContentInterface::class);
        $site = $this->createMock(SiteInterface::class);
        $routePath = $this->createMock(RoutePathInterface::class);
        $pageUrl = new PageUrl($site, $routePath, '/en/services', 'https://example.org/en/services', 'en');

        $pageUrlResolver = $this->createMock(PageUrlResolver::class);
        $pageUrlResolver->method('resolve')->with($content)->willReturn([$pageUrl]);

        $configuration = new PlausibleConfiguration(true, 'https://plausible.example.org', 'secret', 'example.org');
        $configurationResolver = $this->createMock(PlausibleConfigurationResolver::class);
        $configurationResolver->method('resolve')->with($site)->willReturn($configuration);

        $statsClient = $this->createMock(PlausibleStatsClient::class);
        $statsClient->method('emptyMetrics')->willReturn([
            'visitors' => 0,
            'visits' => 0,
            'pageviews' => 0,
            'views_per_visit' => 0,
            'bounce_rate' => 0,
            'time_on_page' => 0,
        ]);
        $statsClient->expects($this->once())
            ->method('getPageMetrics')
            ->with($configuration, '/en/services', '30d')
            ->willReturn([
                'visitors' => 12,
                'visits' => 14,
                'pageviews' => 25,
                'views_per_visit' => 1.8,
                'bounce_rate' => 32.5,
                'time_on_page' => 44,
            ]);

        $provider = new ContentAnalyticsProvider($pageUrlResolver, $configurationResolver, $statsClient);

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
