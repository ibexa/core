<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\RepositoryAware;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Depth as DepthMatcher;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversMethod(DepthMatcher::class, 'matchLocation')]
#[CoversMethod(MultipleValued::class, 'setMatchingConfig')]
#[CoversMethod(DepthMatcher::class, 'matchContentInfo')]
#[CoversMethod(RepositoryAware::class, 'setRepository')]
class DepthTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Depth */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new DepthMatcher();
    }

    /**
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchLocationProvider')]
    public function testMatchLocation($matchingConfig, int $depth, $expectedResult)
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($this->getLocationMock(['depth' => $depth])));
    }

    public static function matchLocationProvider()
    {
        return [
            [
                1,
                1,
                true,
            ],
            [
                1,
                2,
                false,
            ],
            [
                [1, 3],
                2,
                false,
            ],
            [
                [1, 3],
                3,
                true,
            ],
            [
                [1, 3],
                0,
                false,
            ],
            [
                [0, 1],
                0,
                true,
            ],
        ];
    }

    /**
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchContentInfoProvider')]
    public function testMatchContentInfo($matchingConfig, int $depth, $expectedResult)
    {
        $this->matcher->setRepository($this->generateRepositoryMockForDepth($depth));
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock(['mainLocationId' => 42]))
        );
    }

    public static function matchContentInfoProvider()
    {
        return [
            [
                1,
                1,
                true,
            ],
            [
                1,
                2,
                false,
            ],
            [
                [1, 3],
                2,
                false,
            ],
            [
                [1, 3],
                3,
                true,
            ],
        ];
    }

    /**
     * Returns a Repository mock configured to return the appropriate Location object with given parent location Id.
     *
     * @param int $depth
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function generateRepositoryMockForDepth($depth)
    {
        $locationServiceMock = $this->createMock(LocationService::class);
        $locationServiceMock->expects(self::once())
            ->method('loadLocation')
            ->with(42)
            ->will(
                self::returnValue(
                    $this->getLocationMock(['depth' => $depth])
                )
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::once())
            ->method('getLocationService')
            ->will(self::returnValue($locationServiceMock));
        $repository
            ->expects(self::once())
            ->method('getPermissionResolver')
            ->will(self::returnValue($this->getPermissionResolverMock()));

        return $repository;
    }
}
