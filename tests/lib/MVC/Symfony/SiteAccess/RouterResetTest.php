<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\MatcherBuilder;
use Ibexa\Core\MVC\Symfony\SiteAccess\Provider\StaticSiteAccessProvider;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * A reused kernel (persistent PHP worker) resets services between requests.
 * Every request must be matched on its own, not get the SiteAccess of the first one.
 *
 * @covers \Ibexa\Core\MVC\Symfony\SiteAccess\Router::reset
 */
final class RouterResetTest extends TestCase
{
    /**
     * @dataProvider provideRequestsPerMatchingType
     *
     * @param array<string, array<string, string>> $matchConfiguration
     * @param array<string, SimplifiedRequest> $requestsBySiteAccess
     */
    public function testResetMatchesEachRequestAgain(
        array $matchConfiguration,
        array $requestsBySiteAccess
    ): void {
        $router = new Router(
            new MatcherBuilder(),
            new NullLogger(),
            'site',
            $matchConfiguration,
            new StaticSiteAccessProvider(['site', 'admin'])
        );

        foreach (['site', 'admin', 'site'] as $expectedSiteAccess) {
            $request = $requestsBySiteAccess[$expectedSiteAccess];

            self::assertSame($expectedSiteAccess, $router->match($request)->name);
            self::assertSame($request, $router->getRequest());

            $router->reset();
        }
    }

    public function testResetForgetsTheMatchedSiteAccessAndRequest(): void
    {
        $router = new Router(
            new MatcherBuilder(),
            new NullLogger(),
            'site',
            ['Map\Host' => ['admin.example.com' => 'admin']],
            new StaticSiteAccessProvider(['site', 'admin'])
        );
        $router->match(new SimplifiedRequest(['host' => 'admin.example.com', 'pathinfo' => '/']));

        $router->reset();

        self::assertNull($router->getSiteAccess());
        self::assertEquals(new SimplifiedRequest(), $router->getRequest());
    }

    /**
     * @return iterable<string, array{array<string, array<string, string>>, array<string, SimplifiedRequest>}>
     */
    public function provideRequestsPerMatchingType(): iterable
    {
        yield 'URI' => [
            ['Map\URI' => ['site' => 'site', 'admin' => 'admin']],
            [
                'site' => new SimplifiedRequest(['pathinfo' => '/site/api/ibexa/v2/']),
                'admin' => new SimplifiedRequest(['pathinfo' => '/admin/api/ibexa/v2/']),
            ],
        ];

        yield 'host' => [
            ['Map\Host' => ['site.example.com' => 'site', 'admin.example.com' => 'admin']],
            [
                'site' => new SimplifiedRequest(['host' => 'site.example.com', 'pathinfo' => '/']),
                'admin' => new SimplifiedRequest(['host' => 'admin.example.com', 'pathinfo' => '/']),
            ],
        ];

        yield 'X-Siteaccess header' => [
            [],
            [
                'site' => new SimplifiedRequest(['pathinfo' => '/', 'headers' => ['x-siteaccess' => ['site']]]),
                'admin' => new SimplifiedRequest(['pathinfo' => '/', 'headers' => ['x-siteaccess' => ['admin']]]),
            ],
        ];
    }
}
