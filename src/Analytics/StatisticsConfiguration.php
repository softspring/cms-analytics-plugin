<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

final readonly class StatisticsConfiguration
{
    /**
     * @param string[]             $missingReasons
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $provider,
        public bool $enabled,
        public bool $usable,
        public array $missingReasons = [],
        public ?string $dashboardUrl = null,
        public array $context = [],
    ) {}

    public function isUsable(): bool
    {
        return $this->usable;
    }

    public function dashboardUrl(string $path): ?string
    {
        return $this->dashboardUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'enabled' => $this->enabled,
            'usable' => $this->usable,
            'missingReasons' => $this->missingReasons,
            'dashboardUrl' => $this->dashboardUrl,
            'context' => $this->context,
        ];
    }
}
