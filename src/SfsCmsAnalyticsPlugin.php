<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin;

use Softspring\CmsBundle\Plugin\SfsCmsPlugin;

use function dirname;

class SfsCmsAnalyticsPlugin extends SfsCmsPlugin
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public static function getAlias(): string
    {
        return 'sfs_cms_analytics';
    }
}
