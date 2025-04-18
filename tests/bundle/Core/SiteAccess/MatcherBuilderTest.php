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
    private SiteAccessMatcherRegistryInterface & MockObject $siteAccessMatcherRegistry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siteAccessMatcherRegistry = $this->createMock(SiteAccessMatcherRegistryInterface::class);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testBuildMatcherNoService(): void
    {
        $this->siteAccessMatcherRegistry
            ->expects(self::never())
            ->method('getMatcher');
        $matcherBuilder = new MatcherBuilder($this->siteAccessMatcherRegistry);
        $matcher = $this->createMock(Matcher::class);
        $builtMatcher = $matcherBuilder->buildMatcher('\\' . get_class($matcher), [], new SimplifiedRequest());
        self::assertInstanceOf(get_class($matcher), $builtMatcher);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testBuildMatcherService(): void
    {
        $serviceId = 'foo';
        $matcher = $this->createMock(CoreMatcher::class);
        $this->siteAccessMatcherRegistry
            ->expects(self::once())
            ->method('getMatcher')
            ->with($serviceId)
            ->willReturn($matcher);

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
