<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PlausibleStatsClient
{
    public const METRICS = [
        'visitors',
        'visits',
        'pageviews',
        'views_per_visit',
        'bounce_rate',
        'time_on_page',
    ];

    public function __construct(
        protected HttpClientInterface $httpClient,
        protected CacheInterface $cache,
    ) {
    }

    /**
     * @return array<string, int|float|null>
     */
    public function getPageMetrics(PlausibleConfiguration $configuration, string $path, string $dateRange): array
    {
        if (!$configuration->isUsable()) {
            return $this->emptyMetrics();
        }

        $cacheKey = 'sfs_cms_analytics_plausible_'.hash('sha256', $configuration->apiBaseUrl.'|'.$configuration->siteId.'|'.$path.'|'.$dateRange);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($configuration, $path, $dateRange): array {
            $item->expiresAfter(900);

            return $this->fetchPageMetrics($configuration, $path, $dateRange);
        });
    }

    /**
     * @return array<string, int|float|null>
     */
    private function fetchPageMetrics(PlausibleConfiguration $configuration, string $path, string $dateRange): array
    {
        $metrics = $this->fetchMetrics($configuration, $path, $dateRange, [
            'visitors',
            'visits',
            'pageviews',
            'bounce_rate',
            'time_on_page',
        ], 'event:page');

        return array_replace($metrics, $this->fetchMetrics($configuration, $path, $dateRange, [
            'views_per_visit',
        ], 'visit:entry_page'));
    }

    /**
     * @param string[] $metricNames
     *
     * @return array<string, int|float|null>
     */
    private function fetchMetrics(PlausibleConfiguration $configuration, string $path, string $dateRange, array $metricNames, string $filterDimension): array
    {
        $response = $this->httpClient->request('POST', $configuration->apiBaseUrl.'/api/v2/query', [
            'headers' => [
                'Authorization' => 'Bearer '.$configuration->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'site_id' => $configuration->siteId,
                'metrics' => $metricNames,
                'date_range' => $dateRange,
                'filters' => [
                    ['is', $filterDimension, [$path]],
                ],
            ],
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new RuntimeException(sprintf('Plausible API returned HTTP %s for path "%s".', $response->getStatusCode(), $path));
        }

        $payload = $response->toArray(false);
        $values = $payload['results'][0]['metrics'] ?? [];
        $metrics = [];

        foreach ($metricNames as $index => $metric) {
            $metrics[$metric] = $values[$index] ?? 0;
        }

        return $metrics;
    }

    /**
     * @return array<string, int|float|null>
     */
    public function emptyMetrics(): array
    {
        return array_fill_keys(self::METRICS, 0);
    }
}
