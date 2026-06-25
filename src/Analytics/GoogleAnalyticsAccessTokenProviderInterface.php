<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

interface GoogleAnalyticsAccessTokenProviderInterface
{
    public function getAccessToken(GoogleAnalyticsConfiguration $configuration): string;
}
