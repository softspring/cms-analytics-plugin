<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

final readonly class PageStatisticsQuery
{
    public function __construct(
        public string $dateRange,
        public string $metric,
        public string $orderDirection,
        public int $page,
        public int $limit,
        public ?string $path = null,
    ) {
    }
}
