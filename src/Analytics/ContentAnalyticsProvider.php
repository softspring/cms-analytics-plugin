<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use Softspring\CmsBundle\Model\ContentInterface;
use Throwable;

use function array_fill_keys;

class ContentAnalyticsProvider
{
    public function __construct(
        protected PageUrlResolver $pageUrlResolver,
        protected StatisticsProviderChain $statisticsProviderChain,
    ) {
    }

    /**
     * @return array<int, array{
     *     pageUrl: PageUrl,
     *     configuration: StatisticsConfiguration,
     *     metrics: array<string, int|float|null>,
     *     error: string|null,
     * }>
     */
    public function getRows(ContentInterface $content, string $dateRange): array
    {
        $rows = [];

        foreach ($this->pageUrlResolver->resolve($content) as $pageUrl) {
            $provider = $this->statisticsProviderChain->getProvider($pageUrl->site, $pageUrl->path);
            $configuration = $provider?->resolveConfiguration($pageUrl->site, $pageUrl->path) ?? new StatisticsConfiguration(
                provider: 'none',
                enabled: false,
                usable: false,
                missingReasons: ['No analytics statistics provider is configured.'],
            );
            $metrics = $this->emptyMetrics();
            $error = null;

            if ($provider && $configuration->usable) {
                try {
                    $metrics = $provider->getSiteMetrics($pageUrl->site, $dateRange, $pageUrl->path);
                } catch (Throwable $exception) {
                    $error = $exception->getMessage();
                }
            }

            $rows[] = [
                'pageUrl' => $pageUrl,
                'configuration' => $configuration,
                'metrics' => $metrics,
                'error' => $error,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array{metrics: array<string, int|float|null>}> $rows
     *
     * @return array<string, int|float|null>
     */
    public function buildTotals(array $rows): array
    {
        $totals = $this->emptyMetrics();

        foreach ($rows as $row) {
            foreach ($totals as $metric => $value) {
                $totals[$metric] = (float) $value + (float) ($row['metrics'][$metric] ?? 0);
            }
        }

        return $totals;
    }

    /**
     * @return array<string, int|float|null>
     */
    private function emptyMetrics(): array
    {
        return array_fill_keys(StatisticsMetrics::ALL, 0);
    }
}
