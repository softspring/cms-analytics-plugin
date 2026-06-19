<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Admin\Menu;

use Softspring\CmsBundle\Admin\Menu\AbstractContentMenuProvider;
use Softspring\CmsBundle\Admin\Menu\MenuHelper;
use Softspring\CmsBundle\Admin\Menu\MenuItem;
use Softspring\CmsBundle\Config\Exception\InvalidContentException;

class ContentMenuProvider extends AbstractContentMenuProvider
{
    public static function getPriority(): int
    {
        return 252;
    }

    /**
     * @throws InvalidContentException
     */
    public function getMenu(array $menu, ?string $currentSelection = null, ?object $entity = null): array
    {
        [$content, $contentType] = $this->getContent(['content' => $entity]);

        $item = new MenuItem(
            'statistics',
            $this->translator->trans('tabs_menu.statistics', [], 'sfs_cms_analytics'),
            $this->router->generate("sfs_cms_admin_content_{$contentType}_statistics", ['content' => $content->getId()]),
            'statistics' === $currentSelection,
            false,
        );

        $index = MenuHelper::getMenuIndex('routes', $menu);
        if (false === $index) {
            $menu[] = $item;

            return $menu;
        }

        return array_merge(array_slice($menu, 0, $index), [$item], array_slice($menu, $index));
    }
}
