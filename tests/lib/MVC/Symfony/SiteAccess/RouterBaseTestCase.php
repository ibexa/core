<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\MatcherBuilder;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface;
use PHPUnit\Framework\TestCase;

abstract class RouterBaseTestCase extends TestCase
{
    protected const UNDEFINED_SA_NAME = 'undefined_sa';
    protected const ENV_SA_NAME = 'env_sa';
    protected const HEADERBASED_SA_NAME = 'headerbased_sa';

    protected const DEFAULT_SA_NAME = 'default_sa';

    /** @var MatcherBuilder */
    protected $matcherBuilder;

    /** @var SiteAccessProviderInterface */
    protected $siteAccessProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcherBuilder = new MatcherBuilder();
        $this->siteAccessProvider = $this->createSiteAccessProviderMock();
    }

    public function testConstruct(): Router
    {
        return $this->createRouter();
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatch(
        SimplifiedRequest $request,
        string $siteAccess
    ) {
        $router = $this->createRouter();
        $sa = $router->match($request);
        self::assertInstanceOf(SiteAccess::class, $sa);
        self::assertSame($siteAccess, $sa->name);
        // SiteAccess must be serializable as a whole. See https://issues.ibexa.co/browse/EZP-21613
        self::assertIsString(serialize($sa));
        $router->setSiteAccess();
    }

    abstract public function matchProvider(): array;

    abstract protected function createRouter(): Router;

    private function createSiteAccessProviderMock(): SiteAccessProviderInterface
    {
        $isDefinedMap = [];
        $getSiteAccessMap = [];
        foreach ($this->getSiteAccessProviderSettings() as $sa) {
            $isDefinedMap[] = [$sa->name, $sa->isDefined];
            $getSiteAccessMap[] = [
                $sa->name,
                new SiteAccess(
                    $sa->name,
                    $sa->matchingType
                ),
            ];
        }
        $siteAccessProviderMock = $this->createMock(SiteAccessProviderInterface::class);
        $siteAccessProviderMock
            ->method('isDefined')
            ->willReturnMap($isDefinedMap);
        $siteAccessProviderMock
            ->method('getSiteAccess')
            ->willReturnMap($getSiteAccessMap);

        return $siteAccessProviderMock;
    }

    /**
     * @return SiteAccessSetting[]
     */
    abstract public function getSiteAccessProviderSettings(): array;
}
