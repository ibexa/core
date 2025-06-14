<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Exception\InvalidSiteAccessException;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\MatcherBuilderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class RouterTest extends RouterBaseTestCase
{
    protected function tearDown(): void
    {
        putenv('EZPUBLISH_SITEACCESS');
        parent::tearDown();
    }

    public function testConstructDebug()
    {
        return $this->createRouter(true);
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatch(SimplifiedRequest $request, $siteAccess)
    {
        $router = $this->createRouter();
        $sa = $router->match($request);
        self::assertInstanceOf(SiteAccess::class, $sa);
        self::assertSame($siteAccess, $sa->name);
        // SiteAccess must be serializable as a whole. See https://issues.ibexa.co/browse/EZP-21613
        self::assertIsString(serialize($sa));
        $router->setSiteAccess();
    }

    public function testMatchWithDevEnvFail()
    {
        $router = $this->createRouter(true);
        putenv('EZPUBLISH_SITEACCESS=' . self::UNDEFINED_SA_NAME);

        $this->expectException(InvalidSiteAccessException::class);
        $this->expectExceptionMessageMatches(
            '/^Invalid SiteAccess \'' . self::UNDEFINED_SA_NAME . '\', matched by .+\\. Valid SiteAccesses are/'
        );

        $router->match(new SimplifiedRequest());
    }

    public function testMatchWithProdEnvFail()
    {
        $router = $this->createRouter();
        putenv('EZPUBLISH_SITEACCESS=' . self::UNDEFINED_SA_NAME);

        $this->expectException(InvalidSiteAccessException::class);
        $this->expectExceptionMessageMatches(
            '/^Invalid SiteAccess \'' . self::UNDEFINED_SA_NAME . '\', matched by .+\\.$/'
        );

        $router->match(new SimplifiedRequest());
    }

    public function testMatchWithEnv()
    {
        $router = $this->createRouter();
        putenv('EZPUBLISH_SITEACCESS=' . self::ENV_SA_NAME);
        $sa = $router->match(new SimplifiedRequest());
        self::assertInstanceOf(SiteAccess::class, $sa);
        self::assertSame(self::ENV_SA_NAME, $sa->name);
        self::assertSame('env', $sa->matchingType);
        $router->setSiteAccess();
    }

    public function testMatchWithRequestHeader(): void
    {
        $router = $this->createRouter();
        $request = Request::create('/foo/bar');
        $request->headers->set('X-Siteaccess', self::HEADERBASED_SA_NAME);
        $sa = $router->match(
            new SimplifiedRequest(
                [
                    'headers' => $request->headers->all(),
                ]
            )
        );
        self::assertInstanceOf(SiteAccess::class, $sa);
        self::assertSame(self::HEADERBASED_SA_NAME, $sa->name);
        self::assertSame('header', $sa->matchingType);
        $router->setSiteAccess();
    }

    public function matchProvider(): array
    {
        return [
            [SimplifiedRequest::fromUrl('http://example.com'), 'default_sa'],
            [SimplifiedRequest::fromUrl('https://example.com'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('https://example.com/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com//'), 'default_sa'],
            [SimplifiedRequest::fromUrl('https://example.com//'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:8080/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_siteaccess/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/?first_siteaccess'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/?first_sa'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_salt'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa.foo'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/bar/'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/test/foo/bar/first_sa'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/default_sa'), 'default_sa'],

            [SimplifiedRequest::fromUrl('http://example.com/first_sa'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa//'), 'first_sa'],
            // Double slashes shouldn't be considered as one
            [SimplifiedRequest::fromUrl('http://example.com//first_sa//'), 'default_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa///test'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa/foo'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/first_sa/foo/bar'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:82/first_sa/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://third_siteaccess/first_sa/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_sa/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('https://first_sa/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_sa:81/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:82/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:83/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess/foo/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:82/foo/'), 'first_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:83/foo/'), 'first_sa'],

            [SimplifiedRequest::fromUrl('http://example.com/second_sa'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/second_sa?param1=foo'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com/second_sa/foo/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:82/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:83/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:82/second_sa/'), 'second_sa'],
            [SimplifiedRequest::fromUrl('http://first_siteaccess:83/second_sa/'), 'second_sa'],

            [SimplifiedRequest::fromUrl('http://example.com:81/'), 'third_sa'],
            [SimplifiedRequest::fromUrl('https://example.com:81/'), 'third_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:81/foo'), 'third_sa'],
            [SimplifiedRequest::fromUrl('http://example.com:81/foo/bar'), 'third_sa'],

            [SimplifiedRequest::fromUrl('http://example.com:82/'), 'fourth_sa'],
            [SimplifiedRequest::fromUrl('https://example.com:82/'), 'fourth_sa'],
            [SimplifiedRequest::fromUrl('https://example.com:82/foo'), 'fourth_sa'],

            [SimplifiedRequest::fromUrl('http://fr.ezpublish.dev/eng'), 'fr_eng'],
            [SimplifiedRequest::fromUrl('http://us.ezpublish.dev/fre'), 'fr_us'],
        ];
    }

    public function testMatchByNameInvalidSiteAccess()
    {
        $this->expectException(\InvalidArgumentException::class);

        $matcherBuilder = $this->createMock(MatcherBuilderInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $siteAccessProvider = $this->createMock(SiteAccess\SiteAccessProviderInterface::class);
        $siteAccessProvider
            ->method('isDefined')
            ->with('bar')
            ->willReturn(false);
        $router = new Router($matcherBuilder, $logger, 'default_sa', [], $siteAccessProvider);
        $router->matchByName('bar');
    }

    public function testMatchByName()
    {
        $matcherBuilder = $this->createMock(MatcherBuilderInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $matcherClass = 'Map\Host';
        $matchedSiteAccess = 'foo';
        $matcherConfig = [
            'phoenix-rises.fm' => $matchedSiteAccess,
        ];
        $config = [
            'Map\URI' => ['default' => 'default_sa'],
            $matcherClass => $matcherConfig,
        ];
        $siteAccessProvider = $this->createMock(SiteAccess\SiteAccessProviderInterface::class);
        $siteAccessProvider
            ->method('isDefined')
            ->willReturnMap([
                [$matchedSiteAccess, true],
                ['default_sa', true],
            ]);
        $router = new Router($matcherBuilder, $logger, 'default_sa', $config, $siteAccessProvider);
        $matcherInitialSA = $this->createMock(SiteAccess\URILexer::class);
        $router->setSiteAccess(new SiteAccess('test', 'test', $matcherInitialSA));
        $matcherInitialSA
            ->expects(self::once())
            ->method('analyseURI')
            ->willReturn('');

        $matcher = $this->createMock(VersatileMatcher::class);
        $matcherBuilder
            ->expects(self::exactly(2))
            ->method('buildMatcher')
            ->will(
                self::onConsecutiveCalls(
                    $this->createMock(Matcher::class),
                    $matcher
                )
            );

        $reverseMatchedMatcher = $this->createMock(VersatileMatcher::class);
        $matcher
            ->expects(self::once())
            ->method('reverseMatch')
            ->with($matchedSiteAccess)
            ->will(self::returnValue($reverseMatchedMatcher));

        $siteAccess = $router->matchByName($matchedSiteAccess);
        self::assertInstanceOf(SiteAccess::class, $siteAccess);
        self::assertSame($reverseMatchedMatcher, $siteAccess->matcher);
        self::assertSame($matchedSiteAccess, $siteAccess->name);
    }

    public function testMatchByNameNoVersatileMatcher()
    {
        $matcherBuilder = $this->createMock(MatcherBuilderInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $matcherClass = 'Map\Host';
        $defaultSiteAccess = 'default_sa';
        $matcherConfig = [
            'phoenix-rises.fm' => 'foo',
        ];
        $config = [$matcherClass => $matcherConfig];
        $siteAccessProvider = $this->createMock(SiteAccess\SiteAccessProviderInterface::class);
        $siteAccessProvider
            ->method('isDefined')
            ->willReturnMap([
                [$defaultSiteAccess, true],
                ['foo', true],
            ]);
        $router = new Router($matcherBuilder, $logger, $defaultSiteAccess, $config, $siteAccessProvider);
        $router->setSiteAccess(new SiteAccess('test', 'test'));
        $request = $router->getRequest();
        $matcherBuilder
            ->expects(self::once())
            ->method('buildMatcher')
            ->with($matcherClass, $matcherConfig, $request)
            ->will(self::returnValue($this->createMock(Matcher::class)));

        $logger
            ->expects(self::once())
            ->method('notice');
        self::assertEquals(new SiteAccess($defaultSiteAccess, 'default'), $router->matchByName($defaultSiteAccess));
    }

    protected function createRouter($debug = false): Router
    {
        return new Router(
            $this->matcherBuilder,
            $this->createMock(LoggerInterface::class),
            'default_sa',
            [
                'Map\\URI' => [
                    'first_sa' => 'first_sa',
                    'second_sa' => 'second_sa',
                ],
                'Map\\Host' => [
                    'first_sa' => 'first_sa',
                    'first_siteaccess' => 'first_sa',
                    'third_siteaccess' => 'third_sa',
                ],
                'Map\\Port' => [
                    81 => 'third_sa',
                    82 => 'fourth_sa',
                    83 => 'first_sa',
                    85 => 'first_sa',
                ],
                'Compound\\LogicalAnd' => [
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
                ],
            ],
            $this->siteAccessProvider,
            null,
            $debug
        );
    }

    /**
     * @return \Ibexa\Tests\Core\MVC\Symfony\SiteAccess\SiteAccessSetting[]
     */
    public function getSiteAccessProviderSettings(): array
    {
        return [
            new SiteAccessSetting('first_sa', true),
            new SiteAccessSetting('second_sa', true),
            new SiteAccessSetting('third_sa', true),
            new SiteAccessSetting('fourth_sa', true),
            new SiteAccessSetting('fr_eng', true),
            new SiteAccessSetting('fr_us', true),
            new SiteAccessSetting(self::HEADERBASED_SA_NAME, true),
            new SiteAccessSetting(self::ENV_SA_NAME, true),
            new SiteAccessSetting(self::UNDEFINED_SA_NAME, false),
            new SiteAccessSetting(self::DEFAULT_SA_NAME, true),
        ];
    }
}
