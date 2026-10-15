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
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Depth;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Depth as DepthMatcher;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(DepthMatcher::class)]
#[CoversClass(MultipleValued::class)]
#[CoversClass(RepositoryAware::class)]
class DepthTest extends BaseTestCase
{
    /** @var Depth */
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
    public function testMatchLocation(
        $matchingConfig,
        int $depth,
        $expectedResult
    ): void {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($this->getLocationMock(['depth' => $depth])));
    }

    /**
     * @return array<mixed>
     */
    public static function matchLocationProvider(): array
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
    public function testMatchContentInfo(
        $matchingConfig,
        int $depth,
        $expectedResult
    ): void {
        $this->matcher->setRepository($this->generateRepositoryMockForDepth($depth));
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock(['mainLocationId' => 42]))
        );
    }

    /**
     * @return array<mixed>
     */
    public static function matchContentInfoProvider(): array
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
     * @return MockObject
     */
    private function generateRepositoryMockForDepth($depth)
    {
        $locationServiceMock = $this->createMock(LocationService::class);
        $locationServiceMock->expects($this->once())
            ->method('loadLocation')
            ->with(42)
            ->will(
                $this->returnValue(
                    $this->getLocationMock(['depth' => $depth])
                )
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getLocationService')
            ->will($this->returnValue($locationServiceMock));
        $repository
            ->expects($this->once())
            ->method('getPermissionResolver')
            ->will($this->returnValue($this->getPermissionResolverMock()));

        return $repository;
    }
}
