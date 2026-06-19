<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use Softspring\CmsBundle\Model\SiteInterface;

class PlausibleConfigurationResolver
{
    public function resolve(SiteInterface $site): PlausibleConfiguration
    {
        $siteConfig = $site->getConfig() ?? [];
        $config = $siteConfig['extra']['analytics']['plausible'] ?? [];

        return new PlausibleConfiguration(
            (bool) ($config['enabled'] ?? false),
            rtrim((string) ($config['api_base_url'] ?? 'https://plausible.io'), '/'),
            (string) ($config['api_key'] ?? ''),
            (string) ($config['site_id'] ?? ''),
        );
    }
}
