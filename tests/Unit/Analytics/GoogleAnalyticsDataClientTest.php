<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;
use Softspring\CmsAnalyticsPlugin\Analytics\GoogleAnalyticsAccessTokenProviderInterface;
use Softspring\CmsAnalyticsPlugin\Analytics\GoogleAnalyticsConfiguration;
use Softspring\CmsAnalyticsPlugin\Analytics\GoogleAnalyticsDataClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

require_once __DIR__.'/InMemoryCache.php';

final class GoogleAnalyticsDataClientTest extends TestCase
{
    public function testItQueriesPageMetricsFromGoogleAnalyticsDataApi(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$method, $url, json_decode($options['body'], true), $options['headers']];

            return new MockResponse(json_encode([
                'rows' => [
                    [
                        'metricValues' => [
                            ['value' => '12'],
                            ['value' => '10'],
                            ['value' => '24'],
                            ['value' => '2.4'],
                            ['value' => '0.35'],
                            ['value' => '42.2'],
                        ],
                    ],
                ],
            ]));
        });

        $client = new GoogleAnalyticsDataClient($httpClient, new InMemoryCache(), $this->tokenProvider());
        $metrics = $client->getPageMetrics(new GoogleAnalyticsConfiguration(true, '123456789'), '/es/contacto', '30d');

        $this->assertSame(12.0, $metrics['visitors']);
        $this->assertSame(10.0, $metrics['visits']);
        $this->assertSame(24.0, $metrics['pageviews']);
        $this->assertSame(2.4, $metrics['views_per_visit']);
        $this->assertSame(35.0, $metrics['bounce_rate']);
        $this->assertSame(42.2, $metrics['time_on_page']);

        $this->assertCount(1, $requests);
        $this->assertSame('POST', $requests[0][0]);
        $this->assertSame('https://analyticsdata.googleapis.com/v1beta/properties/123456789:runReport', $requests[0][1]);
        $this->assertSame([['startDate' => '30daysAgo', 'endDate' => 'today']], $requests[0][2]['dateRanges']);
        $this->assertSame([['name' => 'pagePath']], $requests[0][2]['dimensions']);
        $this->assertSame('/es/contacto', $requests[0][2]['dimensionFilter']['filter']['stringFilter']['value']);
        $this->assertStringContainsString('Authorization: Bearer test-token', implode("\n", $requests[0][3]));
    }

    public function testItReturnsEmptyMetricsWhenConfigurationIsNotUsable(): void
    {
        $httpClient = new MockHttpClient(static function (): never {
            self::fail('The HTTP client should not be called when GA4 is not usable.');
        });

        $client = new GoogleAnalyticsDataClient($httpClient, new InMemoryCache(), $this->tokenProvider());
        $metrics = $client->getSiteMetrics(new GoogleAnalyticsConfiguration(false, ''), '30d');

        $this->assertSame([
            'visitors' => 0,
            'visits' => 0,
            'pageviews' => 0,
            'views_per_visit' => 0,
            'bounce_rate' => 0,
            'time_on_page' => 0,
        ], $metrics);
    }

    private function tokenProvider(): GoogleAnalyticsAccessTokenProviderInterface
    {
        return new class implements GoogleAnalyticsAccessTokenProviderInterface {
            public function getAccessToken(GoogleAnalyticsConfiguration $configuration): string
            {
                return 'test-token';
            }
        };
    }
}
