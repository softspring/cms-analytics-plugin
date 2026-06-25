<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use Softspring\CmsBundle\Model\SiteInterface;

use function is_scalar;
use function trim;

class StatisticsProviderChain
{
    private const DEFAULT_PROVIDER = 'plausible';

    /**
     * @param iterable<StatisticsProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    public function getProvider(SiteInterface $site, ?string $path = null): ?StatisticsProviderInterface
    {
        $fallback = null;
        $selectedProvider = $this->resolveSelectedProviderName($site);

        foreach ($this->providers as $provider) {
            if (null !== $selectedProvider) {
                if ($selectedProvider === $provider->getName()) {
                    return $provider;
                }

                continue;
            }

            if (self::DEFAULT_PROVIDER === $provider->getName()) {
                $fallback = $provider;
            }

            $configuration = $provider->resolveConfiguration($site, $path);

            if ($configuration->usable && self::DEFAULT_PROVIDER === $provider->getName()) {
                return $provider;
            }

            if (null === $fallback) {
                $fallback = $provider;
            }
        }

        return $fallback;
    }

    private function resolveSelectedProviderName(SiteInterface $site): ?string
    {
        $siteConfig = $site->getConfig() ?? [];
        $driver = $siteConfig['extra']['analytics']['driver'] ?? null;

        if (!is_scalar($driver)) {
            return null;
        }

        $driver = trim((string) $driver);

        return '' === $driver ? null : $driver;
    }

    /**
     * @return string[]
     */
    public function getProviderNames(): array
    {
        $names = [];

        foreach ($this->providers as $provider) {
            $names[] = $provider->getName();
        }

        return $names;
    }
}
