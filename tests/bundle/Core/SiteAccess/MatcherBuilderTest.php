<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\SiteAccess;

use Ibexa\Bundle\Core\SiteAccess\Matcher as CoreMatcher;
use Ibexa\Bundle\Core\SiteAccess\MatcherBuilder;
use Ibexa\Bundle\Core\SiteAccess\SiteAccessMatcherRegistryInterface;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\SiteAccess\MatcherBuilder
 */
class MatcherBuilderTest extends TestCase
{
    /** @var MockObject */
    private $siteAccessMatcherRegistry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteAccessMatcherRegistry = $this->createMock(SiteAccessMatcherRegistryInterface::class);
    }

    public function testBuildMatcherNoService()
    {
        $this->siteAccessMatcherRegistry
            ->expects(self::never())
            ->method('getMatcher');
        $matcherBuilder = new MatcherBuilder($this->siteAccessMatcherRegistry);
        $matcher = $this->createMock(Matcher::class);
        $builtMatcher = $matcherBuilder->buildMatcher('\\' . get_class($matcher), [], new SimplifiedRequest());
        self::assertInstanceOf(get_class($matcher), $builtMatcher);
    }

    public function testBuildMatcherServiceWrongInterface()
    {
        $this->expectException(\TypeError::class);

        $serviceId = 'foo';
        $this->siteAccessMatcherRegistry
            ->expects(self::once())
            ->method('getMatcher')
            ->with($serviceId)
            ->will(self::returnValue($this->createMock(Matcher::class)));
        $matcherBuilder = new MatcherBuilder($this->siteAccessMatcherRegistry);
        $matcherBuilder->buildMatcher("@$serviceId", [], new SimplifiedRequest());
    }

    public function testBuildMatcherService()
    {
        $serviceId = 'foo';
        $matcher = $this->createMock(CoreMatcher::class);
        $this->siteAccessMatcherRegistry
            ->expects(self::once())
            ->method('getMatcher')
            ->with($serviceId)
            ->will(self::returnValue($matcher));

        $matchingConfig = ['foo' => 'bar'];
        $request = new SimplifiedRequest();
        $matcher
            ->expects(self::once())
            ->method('setMatchingConfiguration')
            ->with($matchingConfig);
        $matcher
            ->expects(self::once())
            ->method('setRequest')
            ->with($request);

        $matcherBuilder = new MatcherBuilder($this->siteAccessMatcherRegistry);
        $matcherBuilder->buildMatcher("@$serviceId", $matchingConfig, $request);
    }
}
