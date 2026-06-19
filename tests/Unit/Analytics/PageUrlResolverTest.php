<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Tests\Unit\Analytics;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Softspring\CmsAnalyticsPlugin\Analytics\PageUrlResolver;
use Softspring\CmsBundle\Entity\Content;
use Softspring\CmsBundle\Entity\Route;
use Softspring\CmsBundle\Entity\RoutePath;
use Softspring\CmsBundle\Entity\Site;
use Softspring\CmsBundle\Routing\UrlGenerator;

class PageUrlResolverTest extends TestCase
{
    public function testItResolvesUniquePageUrlsForContentRoutePathsAndSites(): void
    {
        $site = new Site();
        $site->setId('default');

        $routePath = new RoutePath();
        $routePath->setLocale('en');
        $routePath->setCompiledPath('about');
        $routePath->addSite($site);

        $route = new Route();
        $route->addPath($routePath);

        $content = new class extends Content {};
        $content->addRoute($route);

        $urlGenerator = $this->urlGenerator();
        $urlGenerator
            ->expects($this->once())
            ->method('getPathFixed')
            ->with($routePath, $site)
            ->willReturn('en/about');
        $urlGenerator
            ->expects($this->once())
            ->method('getUrlFixed')
            ->with($routePath, $site)
            ->willReturn('https://example.org/en/about');

        $urls = new PageUrlResolver($urlGenerator)->resolve($content);

        $this->assertCount(1, $urls);
        $this->assertSame($site, $urls[0]->site);
        $this->assertSame($routePath, $urls[0]->routePath);
        $this->assertSame('/en/about', $urls[0]->path);
        $this->assertSame('https://example.org/en/about', $urls[0]->url);
        $this->assertSame('en', $urls[0]->locale);
    }

    public function testItSkipsRoutePathsWithoutCompiledPath(): void
    {
        $route = new Route();
        $route->addPath(new RoutePath());

        $content = new class extends Content {};
        $content->addRoute($route);

        $urlGenerator = $this->urlGenerator();
        $urlGenerator->expects($this->never())->method('getPathFixed');
        $urlGenerator->expects($this->never())->method('getUrlFixed');

        $this->assertSame([], new PageUrlResolver($urlGenerator)->resolve($content));
    }

    /**
     * @return UrlGenerator&MockObject
     */
    private function urlGenerator(): UrlGenerator
    {
        return $this->getMockBuilder(UrlGenerator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPathFixed', 'getUrlFixed'])
            ->getMock();
    }
}
