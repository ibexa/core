<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ParentLocation as ParentLocationIdMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;

class ParentLocationTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ParentLocation */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ParentLocationIdMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ParentLocation::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param int|int[] $matchingConfig
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     * @param bool $expectedResult
     */
    public function testMatchLocation($matchingConfig, Location $location, $expectedResult)
    {
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame($expectedResult, $this->matcher->matchLocation($location));
    }

    public function matchLocationProvider()
    {
        return [
            [
                123,
                $this->getLocationMock(['parentLocationId' => 123]),
                true,
            ],
            [
                123,
                $this->getLocationMock(['parentLocationId' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getLocationMock(['parentLocationId' => 456]),
                false,
            ],
            [
                [123, 789],
                $this->getLocationMock(['parentLocationId' => 789]),
                true,
            ],
        ];
    }

    /**
     * @dataProvider matchContentInfoProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ParentLocation::matchContentInfo
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     * @covers \Ibexa\Core\MVC\RepositoryAware::setRepository
     *
     * @param int|int[] $matchingConfig
     * @param \Ibexa\Contracts\Core\Repository\Repository $repository
     * @param bool $expectedResult
     */
    public function testMatchContentInfo($matchingConfig, Repository $repository, $expectedResult)
    {
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock(['mainLocationId' => 42]))
        );
    }

    public function matchContentInfoProvider()
    {
        return [
            [
                123,
                $this->generateRepositoryMockForParentLocationId(123),
                true,
            ],
            [
                123,
                $this->generateRepositoryMockForParentLocationId(456),
                false,
            ],
            [
                [123, 789],
                $this->generateRepositoryMockForParentLocationId(456),
                false,
            ],
            [
                [123, 789],
                $this->generateRepositoryMockForParentLocationId(789),
                true,
            ],
        ];
    }

    /**
     * Returns a Repository mock configured to return the appropriate Location object with given parent location Id.
     *
     * @param int $parentLocationId
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function generateRepositoryMockForParentLocationId($parentLocationId)
    {
        $locationServiceMock = $this->createMock(LocationService::class);
        $locationServiceMock->expects(self::once())
            ->method('loadLocation')
            ->with(42)
            ->will(
                self::returnValue(
                    $this->getLocationMock(['parentLocationId' => $parentLocationId])
                )
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::once())
            ->method('getLocationService')
            ->will(self::returnValue($locationServiceMock));
        $repository
            ->expects(self::any())
            ->method('getPermissionResolver')
            ->will(self::returnValue($this->getPermissionResolverMock()));

        return $repository;
    }
}
