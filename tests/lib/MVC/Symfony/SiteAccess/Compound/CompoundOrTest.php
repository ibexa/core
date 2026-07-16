<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess\Compound;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound\LogicalOr;
use Ibexa\Core\MVC\Symfony\SiteAccess\MatcherBuilder;
use Ibexa\Core\MVC\Symfony\SiteAccess\MatcherBuilderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompoundOrTest extends TestCase
{
    private MatcherBuilderInterface & MockObject $matcherBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcherBuilder = $this->createMock(MatcherBuilderInterface::class);
    }

    public function testConstruct(): LogicalOr
    {
        return $this->buildMatcher();
    }

    private function buildMatcher(): LogicalOr
    {
        return new LogicalOr(
            [
                [
                    'matchers' => [
                        'Map\\URI' => ['eng' => true],
                        'Map\\Host' => ['fr.ezpublish.dev' => true],
                    ],
                    'match' => 'fr_eng',
                ],
                [
                    'matchers' => [
                        'Map\\URI' => ['fre' => true],
                        'Map\\Host' => ['jp.ezpublish.dev' => true],
                    ],
                    'match' => 'fr_jp',
                ],
            ]
        );
    }

    /**
     * @depends testConstruct
     */
    public function testSetMatcherBuilder(Compound $compoundMatcher): void
    {
        $this->matcherBuilder
            ->expects(self::any())
            ->method('buildMatcher')
            ->will(self::returnValue($this->createMock(Matcher::class)));

        $compoundMatcher->setRequest($this->createMock(SimplifiedRequest::class));
        $compoundMatcher->setMatcherBuilder($this->matcherBuilder);
        $matchers = $compoundMatcher->getSubMatchers();
        self::assertIsArray($matchers);
        foreach ($matchers as $matcher) {
            self::assertInstanceOf(Matcher::class, $matcher);
        }
    }

    /**
     * @dataProvider matchProvider
     *
     * @param SimplifiedRequest $request
     * @param string $expectedMatch
     */
    public function testMatch(
        SimplifiedRequest $request,
        $expectedMatch
    ): void {
        $compoundMatcher = $this->buildMatcher();
        $compoundMatcher->setRequest($request);
        $compoundMatcher->setMatcherBuilder(new MatcherBuilder());
        self::assertSame($expectedMatch, $compoundMatcher->match());
    }

    public function matchProvider(): array
    {
        return [
            [SimplifiedRequest::fromUrl('http://fr.ezpublish.dev/eng'), 'fr_eng'],
            [SimplifiedRequest::fromUrl('http://ezpublish.dev/eng'), 'fr_eng'],
            [SimplifiedRequest::fromUrl('http://fr.ezpublish.dev/fre'), 'fr_eng'],
            [SimplifiedRequest::fromUrl('http://fr.ezpublish.dev/'), 'fr_eng'],
            [SimplifiedRequest::fromUrl('http://us.ezpublish.dev/eng'), 'fr_eng'],
            [SimplifiedRequest::fromUrl('http://us.ezpublish.dev/foo'), false],
            [SimplifiedRequest::fromUrl('http://us.ezpublish.dev/fre'), 'fr_jp'],
            [SimplifiedRequest::fromUrl('http://jp.ezpublish.dev/foo'), 'fr_jp'],
            [SimplifiedRequest::fromUrl('http://ezpublish.dev/fr'), false],
        ];
    }

    public function testReverseMatchSiteAccessNotConfigured(): void
    {
        $compoundMatcher = $this->buildMatcher();
        $this->matcherBuilder
            ->expects(self::any())
            ->method('buildMatcher')
            ->will(self::returnValue($this->createMock(VersatileMatcher::class)));

        $compoundMatcher->setRequest($this->createMock(SimplifiedRequest::class));
        $compoundMatcher->setMatcherBuilder($this->matcherBuilder);
        self::assertNull($compoundMatcher->reverseMatch('not_configured_sa'));
    }

    public function testReverseMatchNotVersatile(): void
    {
        $request = $this->createMock(SimplifiedRequest::class);
        $siteAccessName = 'fr_eng';
        $mapUriConfig = ['eng' => true];
        $mapHostConfig = ['fr.ezpublish.dev' => true];
        $compoundMatcher = new LogicalOr(
            [
                [
                    'matchers' => [
                        'Map\URI' => $mapUriConfig,
                        'Map\Host' => $mapHostConfig,
                    ],
                    'match' => $siteAccessName,
                ],
            ]
        );
        $compoundMatcher->setRequest($request);

        $matcher1 = $this->getMockBuilder(Matcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['reverseMatch'])
            ->getMockForAbstractClass();
        $matcher2 = $this->getMockBuilder(Matcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['reverseMatch'])
            ->getMockForAbstractClass();

        $this->matcherBuilder
            ->expects(self::exactly(2))
            ->method('buildMatcher')
            ->will(
                self::returnValueMap(
                    [
                        ['Map\URI', $mapUriConfig, $request, $matcher1],
                        ['Map\Host', $mapHostConfig, $request, $matcher2],
                    ]
                )
            );

        $matcher1
            ->expects(self::never())
            ->method('reverseMatch');
        $matcher2
            ->expects(self::never())
            ->method('reverseMatch');

        $compoundMatcher->setMatcherBuilder($this->matcherBuilder);
        self::assertNull($compoundMatcher->reverseMatch($siteAccessName));
    }

    public function testReverseMatchFail(): void
    {
        $request = $this->createMock(SimplifiedRequest::class);
        $siteAccessName = 'fr_eng';
        $mapUriConfig = ['eng' => true];
        $mapHostConfig = ['fr.ezpublish.dev' => true];
        $compoundMatcher = new LogicalOr(
            [
                [
                    'matchers' => [
                        'Map\URI' => $mapUriConfig,
                        'Map\Host' => $mapHostConfig,
                    ],
                    'match' => $siteAccessName,
                ],
            ]
        );
        $compoundMatcher->setRequest($request);

        $matcher1 = $this->createMock(VersatileMatcher::class);
        $matcher2 = $this->createMock(VersatileMatcher::class);
        $this->matcherBuilder
            ->expects(self::exactly(2))
            ->method('buildMatcher')
            ->will(
                self::returnValueMap(
                    [
                        ['Map\URI', $mapUriConfig, $request, $matcher1],
                        ['Map\Host', $mapHostConfig, $request, $matcher2],
                    ]
                )
            );

        $matcher1
            ->expects(self::once())
            ->method('reverseMatch')
            ->with($siteAccessName)
            ->will(self::returnValue(null));
        $matcher2
            ->expects(self::once())
            ->method('reverseMatch')
            ->with($siteAccessName)
            ->will(self::returnValue(null));

        $compoundMatcher->setMatcherBuilder($this->matcherBuilder);
        self::assertNull($compoundMatcher->reverseMatch($siteAccessName));
    }

    public function testReverseMatch1(): void
    {
        $request = $this->createMock(SimplifiedRequest::class);
        $siteAccessName = 'fr_eng';
        $mapUriConfig = ['eng' => true];
        $mapHostConfig = ['fr.ezpublish.dev' => true];
        $compoundMatcher = new LogicalOr(
            [
                [
                    'matchers' => [
                        'Map\URI' => $mapUriConfig,
                        'Map\Host' => $mapHostConfig,
                    ],
                    'match' => $siteAccessName,
                ],
            ]
        );
        $compoundMatcher->setRequest($request);

        $matcher1 = $this->createMock(VersatileMatcher::class);
        $matcher2 = $this->createMock(VersatileMatcher::class);
        $this->matcherBuilder
            ->expects(self::exactly(2))
            ->method('buildMatcher')
            ->will(
                self::returnValueMap(
                    [
                        ['Map\URI', $mapUriConfig, $request, $matcher1],
                        ['Map\Host', $mapHostConfig, $request, $matcher2],
                    ]
                )
            );

        $reverseMatchedMatcher1 = $this->createMock(VersatileMatcher::class);
        $matcher1
            ->expects(self::once())
            ->method('reverseMatch')
            ->with($siteAccessName)
            ->will(self::returnValue($reverseMatchedMatcher1));
        $matcher2
            ->expects(self::never())
            ->method('reverseMatch');

        $compoundMatcher->setMatcherBuilder($this->matcherBuilder);
        $result = $compoundMatcher->reverseMatch($siteAccessName);
        self::assertInstanceOf(LogicalOr::class, $result);
        foreach ($result->getSubMatchers() as $subMatcher) {
            self::assertInstanceOf(VersatileMatcher::class, $subMatcher);
        }
    }

    public function testReverseMatch2(): void
    {
        $request = $this->createMock(SimplifiedRequest::class);
        $siteAccessName = 'fr_eng';
        $mapUriConfig = ['eng' => true];
        $mapHostConfig = ['fr.ezpublish.dev' => true];
        $compoundMatcher = new LogicalOr(
            [
                [
                    'matchers' => [
                        'Map\URI' => $mapUriConfig,
                        'Map\Host' => $mapHostConfig,
                    ],
                    'match' => $siteAccessName,
                ],
            ]
        );
        $compoundMatcher->setRequest($request);

        $matcher1 = $this->createMock(VersatileMatcher::class);
        $matcher2 = $this->createMock(VersatileMatcher::class);
        $this->matcherBuilder
            ->expects(self::exactly(2))
            ->method('buildMatcher')
            ->will(
                self::returnValueMap(
                    [
                        ['Map\URI', $mapUriConfig, $request, $matcher1],
                        ['Map\Host', $mapHostConfig, $request, $matcher2],
                    ]
                )
            );

        $matcher1
            ->expects(self::once())
            ->method('reverseMatch')
            ->with($siteAccessName)
            ->will(self::returnValue(null));
        $reverseMatchedMatcher2 = $this->createMock(VersatileMatcher::class);
        $matcher2
            ->expects(self::once())
            ->method('reverseMatch')
            ->with($siteAccessName)
            ->will(self::returnValue($reverseMatchedMatcher2));

        $compoundMatcher->setMatcherBuilder($this->matcherBuilder);
        $result = $compoundMatcher->reverseMatch($siteAccessName);
        self::assertInstanceOf(LogicalOr::class, $result);
        foreach ($result->getSubMatchers() as $subMatcher) {
            self::assertInstanceOf(VersatileMatcher::class, $subMatcher);
        }
    }

    public function testSerialize(): void
    {
        $matcher = new LogicalOr([]);
        $matcher->setRequest(new SimplifiedRequest('http', '', 80, '/foo/bar'));
        $sa = new SiteAccess('test', 'test', $matcher);
        $serializedSA1 = serialize($sa);

        $matcher->setRequest(new SimplifiedRequest('http', '', 80, '/foo/bar/baz'));
        $serializedSA2 = serialize($sa);

        self::assertSame($serializedSA1, $serializedSA2);
    }
}
