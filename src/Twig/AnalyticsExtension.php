<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Twig;

use Softspring\CmsAnalyticsPlugin\Analytics\ContentAnalyticsProvider;
use Softspring\CmsBundle\Model\ContentInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AnalyticsExtension extends AbstractExtension
{
    public function __construct(
        protected ContentAnalyticsProvider $contentAnalyticsProvider,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sfs_cms_analytics_content_rows', $this->contentRows(...)),
            new TwigFunction('sfs_cms_analytics_content_totals', $this->contentTotals(...)),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function contentRows(ContentInterface $content, string $dateRange = '30d'): array
    {
        return $this->contentAnalyticsProvider->getRows($content, $dateRange);
    }

    /**
     * @param array<int, array{metrics: array<string, int|float|null>}> $rows
     *
     * @return array<string, int|float|null>
     */
    public function contentTotals(array $rows): array
    {
        return $this->contentAnalyticsProvider->buildTotals($rows);
    }
}
