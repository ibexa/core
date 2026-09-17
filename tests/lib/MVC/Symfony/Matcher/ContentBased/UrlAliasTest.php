<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Core\MVC\RepositoryAware;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\UrlAlias as UrlAliasMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UrlAliasMatcher::class)]
#[CoversClass(MultipleValued::class)]
#[CoversClass(RepositoryAware::class)]
class UrlAliasTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\UrlAlias */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new UrlAliasMatcher();
    }

    /**
     * @param string $matchingConfig
     * @param string[] $expectedValues
     */
    #[DataProvider('setMatchingConfigProvider')]
    public function testSetMatchingConfig($matchingConfig, $expectedValues)
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame(
            $this->matcher->getValues(),
            $expectedValues
        );
    }

    public static function setMatchingConfigProvider()
    {
        return [
            ['/foo/bar/', ['foo/bar']],
            ['/foo/bar/', ['foo/bar']],
            ['/foo/bar', ['foo/bar']],
            [['/foo/bar/', 'baz/biz/'], ['foo/bar', 'baz/biz']],
            [['foo/bar', 'baz/biz'], ['foo/bar', 'baz/biz']],
        ];
    }

    /**
     * Returns a Repository mock configured to return the appropriate Section object with given section identifier.
     *
     * @param string $path
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function generateRepositoryMockForUrlAlias($path)
    {
        // First an url alias that will never match, then the right url alias.
        // This ensures to test even if the location has several url aliases.
        $urlAliasList = [
            self::createStub(URLAlias::class),
            $this
                ->getMockBuilder(URLAlias::class)
                ->setConstructorArgs([['path' => $path]])
                ->getMockForAbstractClass(),
        ];

        $urlAliasServiceMock = $this->createMock(URLAliasService::class);
        $matcher = self::exactly(2);
        $urlAliasServiceMock->expects($matcher)
            ->method('listLocationAliases')
            ->willReturnCallback(static function (Location $location, bool $custom = true, ?string $languageCode = null, ?bool $showAllTranslations = null, ?array $prioritizedLanguages = null) use ($matcher, $urlAliasList): array {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertTrue($custom);

                    return [];
                }

                self::assertFalse($custom);

                return $urlAliasList;
            });

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::once())
            ->method('getURLAliasService')
            ->will(self::returnValue($urlAliasServiceMock));

        return $repository;
    }

    /**
     * @param string|string[] $matchingConfig
     * @param string $path
     * @param bool $expectedResult
     */
    #[DataProvider('matchLocationProvider')]
    public function testMatchLocation($matchingConfig, string $path, $expectedResult)
    {
        $repository = $this->generateRepositoryMockForUrlAlias($path);
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame(
            $expectedResult,
            $this->matcher->matchLocation($this->getLocationMock())
        );
    }

    public static function matchLocationProvider()
    {
        return [
            [
                'foo/url',
                '/foo/url',
                true,
            ],
            [
                '/foo/url',
                '/foo/url',
                true,
            ],
            [
                'foo/url',
                '/bar/url',
                false,
            ],
            [
                ['foo/url', 'baz'],
                '/bar/url',
                false,
            ],
            [
                ['foo/url   ', 'baz   '],
                '/baz',
                true,
            ],
        ];
    }

    public function testMatchContentInfo()
    {
        $this->expectException(\RuntimeException::class);

        $this->matcher->setMatchingConfig('foo/bar');
        $this->matcher->matchContentInfo($this->getContentInfoMock());
    }
}
