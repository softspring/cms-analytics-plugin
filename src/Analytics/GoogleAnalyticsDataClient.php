<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function array_fill_keys;
use function array_map;
use function array_replace;
use function date;
use function hash;
use function is_string;
use function max;
use function min;
use function sprintf;
use function strtolower;
use function trim;

final class GoogleAnalyticsDataClient
{
    private const API_BASE_URL = 'https://analyticsdata.googleapis.com/v1beta';

    private const METRIC_MAP = [
        'visitors' => 'activeUsers',
        'visits' => 'sessions',
        'pageviews' => 'screenPageViews',
        'views_per_visit' => 'screenPageViewsPerSession',
        'bounce_rate' => 'bounceRate',
        'time_on_page' => 'averageSessionDuration',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly GoogleAnalyticsAccessTokenProviderInterface $accessTokenProvider,
    ) {
    }

    /**
     * @return array<string, int|float|null>
     */
    public function getSiteMetrics(GoogleAnalyticsConfiguration $configuration, string $dateRange): array
    {
        if (!$configuration->isUsable()) {
            return $this->emptyMetrics();
        }

        return $this->fetchMetrics($configuration, $dateRange);
    }

    /**
     * @return array<string, int|float|null>
     */
    public function getPageMetrics(GoogleAnalyticsConfiguration $configuration, string $path, string $dateRange): array
    {
        if (!$configuration->isUsable()) {
            return $this->emptyMetrics();
        }

        $cacheKey = 'sfs_cms_analytics_ga4_'.hash('sha256', $configuration->propertyId.'|'.$path.'|'.$dateRange);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($configuration, $path, $dateRange): array {
            $item->expiresAfter(900);

            return $this->fetchMetrics($configuration, $dateRange, $path);
        });
    }

    /**
     * @return list<array{path: string, metrics: array<string, int|float|null>}>
     */
    public function queryPages(GoogleAnalyticsConfiguration $configuration, PageStatisticsQuery $query): array
    {
        if (!$configuration->isUsable()) {
            return [];
        }

        $limit = min(500, max(1, $query->limit));
        $payload = $this->basePayload($query->dateRange, ['visitors', 'visits', 'pageviews']);
        $payload['dimensions'] = [['name' => 'pagePath']];
        $payload['orderBys'] = [[
            'metric' => ['metricName' => self::METRIC_MAP[$query->metric] ?? self::METRIC_MAP['pageviews']],
            'desc' => 'desc' === strtolower($query->orderDirection),
        ]];
        $payload['limit'] = (string) $limit;
        $payload['offset'] = (string) ((max(1, $query->page) - 1) * $limit);

        if (null !== $query->path) {
            $payload['dimensionFilter'] = $this->pathFilter($query->path);
        }

        $response = $this->request($configuration, $payload);
        $rows = [];

        foreach ($response['rows'] ?? [] as $row) {
            $path = $row['dimensionValues'][0]['value'] ?? null;
            if (!is_string($path) || '' === trim($path)) {
                continue;
            }

            $rows[] = [
                'path' => $path,
                'metrics' => $this->normalizeMetricValues(['visitors', 'visits', 'pageviews'], $row['metricValues'] ?? []),
            ];
        }

        return $rows;
    }

    /**
     * @param string[] $metricNames
     *
     * @return array<string, mixed>
     */
    private function basePayload(string $dateRange, array $metricNames): array
    {
        return [
            'dateRanges' => [$this->resolveDateRange($dateRange)],
            'metrics' => array_map(
                static fn (string $metric): array => ['name' => self::METRIC_MAP[$metric]],
                $metricNames,
            ),
        ];
    }

    /**
     * @return array<string, int|float|null>
     */
    private function fetchMetrics(GoogleAnalyticsConfiguration $configuration, string $dateRange, ?string $path = null): array
    {
        $metricNames = StatisticsMetrics::ALL;
        $payload = $this->basePayload($dateRange, $metricNames);

        if (null !== $path) {
            $payload['dimensions'] = [['name' => 'pagePath']];
            $payload['dimensionFilter'] = $this->pathFilter($path);
        }

        $response = $this->request($configuration, $payload);
        $metricValues = $response['rows'][0]['metricValues'] ?? [];

        return array_replace($this->emptyMetrics(), $this->normalizeMetricValues($metricNames, $metricValues));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function request(GoogleAnalyticsConfiguration $configuration, array $payload): array
    {
        $response = $this->httpClient->request(
            'POST',
            sprintf('%s/properties/%s:runReport', self::API_BASE_URL, $configuration->propertyId),
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->accessTokenProvider->getAccessToken($configuration),
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ],
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $error = $response->toArray(false)['error']['message'] ?? null;

            throw new RuntimeException(sprintf('Google Analytics Data API returned HTTP %s for property "%s"%s.', $response->getStatusCode(), $configuration->propertyId, $error ? sprintf(': %s', $error) : ''));
        }

        return $response->toArray(false);
    }

    /**
     * @return array<string, mixed>
     */
    private function pathFilter(string $path): array
    {
        return [
            'filter' => [
                'fieldName' => 'pagePath',
                'stringFilter' => [
                    'matchType' => 'EXACT',
                    'value' => $path,
                ],
            ],
        ];
    }

    /**
     * @return array{startDate: string, endDate: string}
     */
    private function resolveDateRange(string $dateRange): array
    {
        return [
            'startDate' => match ($dateRange) {
                '7d' => '7daysAgo',
                '91d' => '91daysAgo',
                '6mo' => '180daysAgo',
                '12mo' => '365daysAgo',
                'year' => date('Y-01-01'),
                default => '30daysAgo',
            },
            'endDate' => 'today',
        ];
    }

    /**
     * @param string[]         $metricNames
     * @param array<int,mixed> $metricValues
     *
     * @return array<string, int|float|null>
     */
    private function normalizeMetricValues(array $metricNames, array $metricValues): array
    {
        $metrics = [];

        foreach ($metricNames as $index => $metricName) {
            $value = (float) ($metricValues[$index]['value'] ?? 0);
            $metrics[$metricName] = 'bounce_rate' === $metricName ? $value * 100 : $value;
        }

        return $metrics;
    }

    /**
     * @return array<string, int|float|null>
     */
    public function emptyMetrics(): array
    {
        return array_fill_keys(StatisticsMetrics::ALL, 0);
    }
}
