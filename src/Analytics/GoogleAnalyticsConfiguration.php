<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use function rtrim;

final readonly class GoogleAnalyticsConfiguration
{
    public function __construct(
        public bool $enabled,
        public string $propertyId,
        public ?string $credentialsJson = null,
        public ?string $credentialsPath = null,
        public string $dashboardBaseUrl = 'https://analytics.google.com/analytics/web',
    ) {}

    public function isUsable(): bool
    {
        return $this->enabled && '' !== $this->propertyId;
    }

    /**
     * @return string[]
     */
    public function missingReasons(): array
    {
        if (!$this->enabled) {
            return ['Google Analytics 4 analytics is disabled for this site.'];
        }

        if ('' === $this->propertyId) {
            return ['Google Analytics 4 property id is missing.'];
        }

        return [];
    }

    public function dashboardUrl(?string $path = null): ?string
    {
        if (!$this->isUsable()) {
            return null;
        }

        return rtrim($this->dashboardBaseUrl, '/') . '/#/p' . $this->propertyId . '/reports/intelligenthome';
    }
}
