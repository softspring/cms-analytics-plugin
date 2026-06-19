<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;
use Softspring\CmsAnalyticsPlugin\Analytics\PlausibleConfigurationResolver;
use Softspring\CmsBundle\Entity\Site;

class PlausibleConfigurationResolverTest extends TestCase
{
    public function testItReadsPlausibleConfigurationFromSiteExtraConfig(): void
    {
        $site = new Site();
        $site->setConfig([
            'extra' => [
                'analytics' => [
                    'plausible' => [
                        'enabled' => true,
                        'api_base_url' => 'https://plausible.example.org/',
                        'api_key' => 'secret',
                        'site_id' => 'example.org',
                    ],
                ],
            ],
        ]);

        $configuration = new PlausibleConfigurationResolver()->resolve($site);

        $this->assertTrue($configuration->enabled);
        $this->assertSame('https://plausible.example.org', $configuration->apiBaseUrl);
        $this->assertSame('secret', $configuration->apiKey);
        $this->assertSame('example.org', $configuration->siteId);
        $this->assertTrue($configuration->isUsable());
    }

    public function testItReturnsDisabledDefaultsWhenSiteHasNoAnalyticsConfiguration(): void
    {
        $configuration = new PlausibleConfigurationResolver()->resolve(new Site());

        $this->assertFalse($configuration->enabled);
        $this->assertSame('https://plausible.io', $configuration->apiBaseUrl);
        $this->assertSame('', $configuration->apiKey);
        $this->assertSame('', $configuration->siteId);
        $this->assertFalse($configuration->isUsable());
        $this->assertSame([
            'Plausible analytics is disabled for this site.',
            'Missing Plausible Stats API key.',
            'Missing Plausible site id.',
        ], $configuration->missingReasons());
    }
}
