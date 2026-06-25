<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;
use Softspring\CmsAnalyticsPlugin\Analytics\GoogleAnalyticsConfigurationResolver;
use Softspring\CmsBundle\Model\SiteInterface;

final class GoogleAnalyticsConfigurationResolverTest extends TestCase
{
    public function testItResolvesGoogleAnalyticsConfigurationFromSiteExtra(): void
    {
        $site = $this->createMock(SiteInterface::class);
        $site->method('getConfig')->willReturn([
            'extra' => [
                'analytics' => [
                    'google_analytics_4' => [
                        'enabled' => true,
                        'property_id' => ' 123456789 ',
                        'credentials_json' => ' {"client_email":"analytics@example.org"} ',
                        'credentials_path' => ' /srv/credentials.json ',
                        'dashboard_base_url' => 'https://analytics.example.org/',
                    ],
                ],
            ],
        ]);

        $configuration = (new GoogleAnalyticsConfigurationResolver())->resolve($site);

        $this->assertTrue($configuration->enabled);
        $this->assertSame('123456789', $configuration->propertyId);
        $this->assertSame('{"client_email":"analytics@example.org"}', $configuration->credentialsJson);
        $this->assertSame('/srv/credentials.json', $configuration->credentialsPath);
        $this->assertSame('https://analytics.example.org', $configuration->dashboardBaseUrl);
        $this->assertTrue($configuration->isUsable());
        $this->assertSame([], $configuration->missingReasons());
    }

    public function testItReturnsDisabledDefaultsWhenSiteHasNoGoogleAnalyticsConfiguration(): void
    {
        $site = $this->createMock(SiteInterface::class);
        $site->method('getConfig')->willReturn([]);

        $configuration = (new GoogleAnalyticsConfigurationResolver())->resolve($site);

        $this->assertFalse($configuration->enabled);
        $this->assertSame('', $configuration->propertyId);
        $this->assertNull($configuration->credentialsJson);
        $this->assertNull($configuration->credentialsPath);
        $this->assertSame('https://analytics.google.com/analytics/web', $configuration->dashboardBaseUrl);
        $this->assertFalse($configuration->isUsable());
        $this->assertSame(['Google Analytics 4 analytics is disabled for this site.'], $configuration->missingReasons());
    }
}
