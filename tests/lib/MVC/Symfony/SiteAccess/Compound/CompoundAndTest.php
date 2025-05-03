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
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound\LogicalAnd;
use Ibexa\Core\MVC\Symfony\SiteAccess\MatcherBuilder;
use Ibexa\Core\MVC\Symfony\SiteAccess\MatcherBuilderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompoundAndTest extends TestCase
{
    private MatcherBuilderInterface & MockObject $matcherBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcherBuilder = $this->createMock(MatcherBuilderInterface::class);
    }

    public function testConstruct(): LogicalAnd
    {
        return $this->buildMatcher();
    }

    /**
     * @return \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound\LogicalAnd
     */
    private function buildMatcher(): LogicalAnd
    {
        return new LogicalAnd(
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
                        'Map\\Host' => ['us.ezpublish.dev' => true],
                    ],
                    'match' => 'fr_us',
                ],
                [
                    'matchers' => [
                        'Map\\URI' => ['de' => true],
                        'Map\\Host' => ['jp.ezpublish.dev' => true],
                    ],
                    'match' => 'de_jp',
                ],
            ]
        );
    }

    /**
     * @depends testConstruct
     */
    public function testSetMatcherBuilder(Compound $compoundMatcher): void
    {
        $this
            ->matcherBuilder
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
     * @param \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest $request
     * @param $expectedMatch
     */
    public function testMatch(SimplifiedRequest $request, $expectedMatch): void
    {
        $compoundMatcher = $this->buildMatcher();
        $compoundMatcher->setRequest($request);
        $compoundMatcher->setMatcherBuilder(new MatcherBuilder());
        self::assertSame($expectedMatch, $compoundMatcher->match());
    }

    public function testSetRequest(): void
    {
        $compoundMatcher = new LogicalAnd(
            [
                [
                    'matchers' => [
                        'Map\\URI' => ['eng' => true],
                        'Map\\Host' => ['fr.ezpublish.dev' => true],
                    ],
                    'match' => 'fr_eng',
                ],
            ]
        );

        $matcher1 = $this->createMock(Matcher::class);
        $matcher2 = $this->createMock(Matcher::class);
        $this->matcherBuilder
            ->expects(self::exactly(2))
            ->method('buildMatcher')
            ->will(self::onConsecutiveCalls($matcher1, $matcher2));

        $request = $this->createMock(SimplifiedRequest::class);
        $matcher1
            ->expects(self::once())
            ->method('setRequest')
            ->with($request);
        $matcher2
            ->expects(self::once())
            ->method('setRequest')
            ->with($request);

        $compoundMatcher->setRequest($this->createMock(SimplifiedRequest::class));
        $compoundMatcher->setMatcherBuilder($this->matcherBuilder);
        $compoundMatcher->setRequest($request);
    }

    public function matchProvider(): array
    {
        return [
            [SimplifiedRequest::fromUrl('http://fr.ezpublish.dev/eng'), 'fr_eng'],
            [SimplifiedRequest::fromUrl('http://ezpublish.dev/eng'), false],
            [SimplifiedRequest::fromUrl('http://fr.ezpublish.dev/fre'), false],
            [SimplifiedRequest::fromUrl('http://fr.ezpublish.dev/'), false],
            [SimplifiedRequest::fromUrl('http://us.ezpublish.dev/eng'), false],
            [SimplifiedRequest::fromUrl('http://us.ezpublish.dev/fre'), 'fr_us'],
            [SimplifiedRequest::fromUrl('http://ezpublish.dev/fr'), false],
            [SimplifiedRequest::fromUrl('http://jp.ezpublish.dev/de'), 'de_jp'],
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
        $compoundMatcher = new LogicalAnd(
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
            ->expects(self::once())
            ->method('reverseMatch')
            ->with($siteAccessName)
            ->will(self::returnValue($this->createMock(VersatileMatcher::class)));
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
        $compoundMatcher = new LogicalAnd(
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
            ->will(self::returnValue($this->createMock(VersatileMatcher::class)));
        $matcher2
            ->expects(self::once())
            ->method('reverseMatch')
            ->with($siteAccessName)
            ->will(self::returnValue(null));

        $compoundMatcher->setMatcherBuilder($this->matcherBuilder);
        self::assertNull($compoundMatcher->reverseMatch($siteAccessName));
    }

    public function testReverseMatch(): void
    {
        $request = $this->createMock(SimplifiedRequest::class);
        $siteAccessName = 'fr_eng';
        $mapUriConfig = ['eng' => true];
        $mapHostConfig = ['fr.ezpublish.dev' => true];
        $compoundMatcher = new LogicalAnd(
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
        $reverseMatchedMatcher2 = $this->createMock(VersatileMatcher::class);
        $matcher2
            ->expects(self::once())
            ->method('reverseMatch')
            ->with($siteAccessName)
            ->will(self::returnValue($reverseMatchedMatcher2));

        $compoundMatcher->setMatcherBuilder($this->matcherBuilder);
        $result = $compoundMatcher->reverseMatch($siteAccessName);
        self::assertInstanceOf(LogicalAnd::class, $result);
        foreach ($result->getSubMatchers() as $subMatcher) {
            self::assertInstanceOf(VersatileMatcher::class, $subMatcher);
        }
    }

    public function testSerialize(): void
    {
        $matcher = new LogicalAnd([]);
        $matcher->setRequest(new SimplifiedRequest('http', '', 80, '/foo/bar'));
        $sa = new SiteAccess('test', 'test', $matcher);
        $serializedSA1 = serialize($sa);

        $matcher->setRequest(new SimplifiedRequest('http', '', 80, '/foo/bar/baz'));
        $serializedSA2 = serialize($sa);

        self::assertSame($serializedSA1, $serializedSA2);
    }
}
