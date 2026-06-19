<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;
use Softspring\CmsAnalyticsPlugin\Analytics\PlausibleConfiguration;
use Softspring\CmsAnalyticsPlugin\Analytics\PlausibleStatsClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PlausibleStatsClientTest extends TestCase
{
    public function testItFetchesAndCachesPageMetrics(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode([
                'results' => [
                    ['metrics' => [10, 4, 6, 1.67, 25.5, 38]],
                ],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new PlausibleStatsClient($httpClient, new InMemoryCache());

        $configuration = new PlausibleConfiguration(true, 'https://plausible.example.org', 'secret', 'example.org');

        $metrics = $client->getPageMetrics($configuration, '/blog/post', '30d');
        $cachedMetrics = $client->getPageMetrics($configuration, '/blog/post', '30d');

        $this->assertSame([
            'visitors' => 10,
            'visits' => 4,
            'pageviews' => 6,
            'views_per_visit' => 1.67,
            'bounce_rate' => 25.5,
            'time_on_page' => 38,
        ], $metrics);
        $this->assertSame($metrics, $cachedMetrics);
        $this->assertSame(1, $httpClient->getRequestsCount());
    }

    public function testItReturnsEmptyMetricsWhenConfigurationIsNotUsable(): void
    {
        $httpClient = new MockHttpClient();
        $client = new PlausibleStatsClient($httpClient, new InMemoryCache());

        $metrics = $client->getPageMetrics(new PlausibleConfiguration(false, 'https://plausible.io', '', ''), '/blog/post', '30d');

        $this->assertSame([
            'visitors' => 0,
            'visits' => 0,
            'pageviews' => 0,
            'views_per_visit' => 0,
            'bounce_rate' => 0,
            'time_on_page' => 0,
        ], $metrics);
        $this->assertSame(0, $httpClient->getRequestsCount());
    }

    public function testItThrowsWhenPlausibleReturnsAnErrorStatus(): void
    {
        $client = new PlausibleStatsClient(
            new MockHttpClient([new MockResponse('', ['http_code' => 401])]),
            new InMemoryCache(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Plausible API returned HTTP 401 for path "/blog/post".');

        $client->getPageMetrics(new PlausibleConfiguration(true, 'https://plausible.io', 'secret', 'example.org'), '/blog/post', '30d');
    }
}
