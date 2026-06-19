<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

final readonly class PlausibleConfiguration
{
    public function __construct(
        public bool $enabled,
        public string $apiBaseUrl,
        public string $apiKey,
        public string $siteId,
    ) {
    }

    public function isUsable(): bool
    {
        return $this->enabled && '' !== $this->apiKey && '' !== $this->siteId;
    }

    public function dashboardUrl(string $path): ?string
    {
        if (!$this->isUsable()) {
            return null;
        }

        $encodedPath = str_replace('%2F', '/', rawurlencode($path));

        return sprintf('%s/%s?f=is,page,%s', $this->apiBaseUrl, rawurlencode($this->siteId), $encodedPath);
    }

    /**
     * @return string[]
     */
    public function missingReasons(): array
    {
        $reasons = [];

        if (!$this->enabled) {
            $reasons[] = 'Plausible analytics is disabled for this site.';
        }

        if ('' === $this->apiKey) {
            $reasons[] = 'Missing Plausible Stats API key.';
        }

        if ('' === $this->siteId) {
            $reasons[] = 'Missing Plausible site id.';
        }

        return $reasons;
    }
}
