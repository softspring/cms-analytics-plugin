<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Admin\Routing;

use Softspring\CmsBundle\Routing\Provider\RoutingProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ContentTypeRoutingProvider implements RoutingProviderInterface
{
    public function supportedTypes(): array
    {
        return ['sfs_cms_plugin_admin_content_type'];
    }

    public function supports(string $type): bool
    {
        return \in_array($type, $this->supportedTypes(), true);
    }

    public function getAdminRoutes(string $type): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->add('statistics', new Route('/{content}/statistics', [
            '_controller' => 'sfs_cms.analytics_plugin.admin.content_statistics.controller::statistics',
        ]));

        return $collection;
    }
}
