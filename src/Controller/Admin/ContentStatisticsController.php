<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Controller\Admin;

use Softspring\CmsAnalyticsPlugin\Analytics\ContentAnalyticsProvider;
use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Manager\ContentManagerInterface;
use Softspring\CmsBundle\Model\ContentInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentStatisticsController extends AbstractController
{
    private const ALLOWED_RANGES = ['7d', '30d', '91d', '6mo', '12mo', 'year'];

    public function __construct(
        protected CmsConfig $cmsConfig,
        protected ContentManagerInterface $contentManager,
        protected ContentAnalyticsProvider $contentAnalyticsProvider,
    ) {}

    public function statistics(Request $request): Response
    {
        $content = $this->loadContent($request);
        $contentType = (string) $request->attributes->get('_content_type');
        $dateRange = (string) $request->query->get('range', '30d');
        if (!\in_array($dateRange, self::ALLOWED_RANGES, true)) {
            $dateRange = '30d';
        }

        $rows = $this->contentAnalyticsProvider->getRows($content, $dateRange);
        $totals = $this->contentAnalyticsProvider->buildTotals($rows);

        return $this->render('@SfsCmsAnalyticsPlugin/admin/content/statistics.html.twig', [
            'content' => $content,
            'entity' => $content,
            'content_entity' => $content,
            'content_type' => $contentType,
            'content_config' => $this->cmsConfig->getContent($contentType),
            'rows' => $rows,
            'totals' => $totals,
            'summary' => $this->buildSummary($rows, $totals),
            'date_range' => $dateRange,
            'allowed_ranges' => self::ALLOWED_RANGES,
        ]);
    }

    private function loadContent(Request $request): ContentInterface
    {
        $contentType = (string) $request->attributes->get('_content_type');
        $contentId = (string) $request->attributes->get('content');

        if ('' === $contentType || '' === $contentId) {
            throw new NotFoundHttpException('Content not found.');
        }

        $content = $this->contentManager->getRepository($contentType)->findOneBy(['id' => $contentId]);
        if (!$content instanceof ContentInterface) {
            throw new NotFoundHttpException('Content not found.');
        }

        return $content;
    }

    /**
     * @param  array<int, array{metrics: array<string, int|float|null>}> $rows
     * @param  array<string, int|float|null>                             $totals
     * @return array<string, float>
     */
    private function buildSummary(array $rows, array $totals): array
    {
        $weightedBounceRate = 0.0;
        $weightedTimeOnPage = 0.0;
        $visits = (float) ($totals['visits'] ?? 0);

        foreach ($rows as $row) {
            $rowVisits = (float) ($row['metrics']['visits'] ?? 0);
            $weightedBounceRate += (float) ($row['metrics']['bounce_rate'] ?? 0) * $rowVisits;
            $weightedTimeOnPage += (float) ($row['metrics']['time_on_page'] ?? 0) * $rowVisits;
        }

        return [
            'views_per_visit' => $visits > 0 ? (float) ($totals['pageviews'] ?? 0) / $visits : 0.0,
            'bounce_rate' => $visits > 0 ? $weightedBounceRate / $visits : 0.0,
            'time_on_page' => $visits > 0 ? $weightedTimeOnPage / $visits : 0.0,
        ];
    }
}
