<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Depth;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Depth as DepthMatcher;
use PHPUnit\Framework\MockObject\MockObject;

class DepthTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Depth */
    private Depth $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new DepthMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Depth::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param int|int[] $matchingConfig
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     * @param bool $expectedResult
     */
    public function testMatchLocation(int|array $matchingConfig, Location $location, bool $expectedResult): void
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($location));
    }

    public function matchLocationProvider(): array
    {
        return [
            [
                1,
                $this->getLocationMock(['depth' => 1]),
                true,
            ],
            [
                1,
                $this->getLocationMock(['depth' => 2]),
                false,
            ],
            [
                [1, 3],
                $this->getLocationMock(['depth' => 2]),
                false,
            ],
            [
                [1, 3],
                $this->getLocationMock(['depth' => 3]),
                true,
            ],
            [
                [1, 3],
                $this->getLocationMock(['depth' => 0]),
                false,
            ],
            [
                [0, 1],
                $this->getLocationMock(['depth' => 0]),
                true,
            ],
        ];
    }

    /**
     * @dataProvider matchContentInfoProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Depth::matchContentInfo
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     * @covers \Ibexa\Core\MVC\RepositoryAware::setRepository
     *
     * @param int|int[] $matchingConfig
     * @param \Ibexa\Contracts\Core\Repository\Repository $repository
     * @param bool $expectedResult
     */
    public function testMatchContentInfo(int|array $matchingConfig, Repository $repository, bool $expectedResult): void
    {
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock(['mainLocationId' => 42]))
        );
    }

    public function matchContentInfoProvider(): array
    {
        return [
            [
                1,
                $this->generateRepositoryMockForDepth(1),
                true,
            ],
            [
                1,
                $this->generateRepositoryMockForDepth(2),
                false,
            ],
            [
                [1, 3],
                $this->generateRepositoryMockForDepth(2),
                false,
            ],
            [
                [1, 3],
                $this->generateRepositoryMockForDepth(3),
                true,
            ],
        ];
    }

    /**
     * Returns a Repository mock configured to return the appropriate Location object with the given parent location Id.
     */
    private function generateRepositoryMockForDepth(int $depth): Repository & MockObject
    {
        $locationServiceMock = $this->createMock(LocationService::class);
        $locationServiceMock->expects(self::once())
            ->method('loadLocation')
            ->with(42)
            ->willReturn(
                $this->getLocationMock(['depth' => $depth])
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::once())
            ->method('getLocationService')
            ->willReturn($locationServiceMock);

        return $repository;
    }
}
