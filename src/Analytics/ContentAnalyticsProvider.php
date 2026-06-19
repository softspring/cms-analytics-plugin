<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use Softspring\CmsBundle\Model\ContentInterface;
use Throwable;

class ContentAnalyticsProvider
{
    public function __construct(
        protected PageUrlResolver $pageUrlResolver,
        protected PlausibleConfigurationResolver $plausibleConfigurationResolver,
        protected PlausibleStatsClient $plausibleStatsClient,
    ) {
    }

    /**
     * @return array<int, array{
     *     pageUrl: PageUrl,
     *     configuration: PlausibleConfiguration,
     *     metrics: array<string, int|float|null>,
     *     error: string|null,
     * }>
     */
    public function getRows(ContentInterface $content, string $dateRange): array
    {
        $rows = [];

        foreach ($this->pageUrlResolver->resolve($content) as $pageUrl) {
            $configuration = $this->plausibleConfigurationResolver->resolve($pageUrl->site);
            $metrics = $this->plausibleStatsClient->emptyMetrics();
            $error = null;

            if ($configuration->isUsable()) {
                try {
                    $metrics = $this->plausibleStatsClient->getPageMetrics($configuration, $pageUrl->path, $dateRange);
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
        $totals = $this->plausibleStatsClient->emptyMetrics();

        foreach ($rows as $row) {
            foreach ($totals as $metric => $value) {
                $totals[$metric] = (float) $value + (float) ($row['metrics'][$metric] ?? 0);
            }
        }

        return $totals;
    }
}
