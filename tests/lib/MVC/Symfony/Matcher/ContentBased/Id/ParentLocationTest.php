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
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTest;
use PHPUnit\Framework\MockObject\MockObject;

class ParentLocationTest extends BaseTest
{
    /** @var ParentLocationIdMatcher */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ParentLocationIdMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \ParentLocationIdMatcher::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param int|int[] $matchingConfig
     * @param Location $location
     * @param bool $expectedResult
     */
    public function testMatchLocation(
        $matchingConfig,
        Location $location,
        $expectedResult
    ) {
        $this->matcher->setMatchingConfig($matchingConfig);
        $this->assertSame($expectedResult, $this->matcher->matchLocation($location));
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
     * @covers \ParentLocationIdMatcher::matchContentInfo
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     * @covers \Ibexa\Core\MVC\RepositoryAware::setRepository
     *
     * @param int|int[] $matchingConfig
     * @param Repository $repository
     * @param bool $expectedResult
     */
    public function testMatchContentInfo(
        $matchingConfig,
        Repository $repository,
        $expectedResult
    ) {
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);
        $this->assertSame(
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
     * @return MockObject
     */
    private function generateRepositoryMockForParentLocationId($parentLocationId)
    {
        $locationServiceMock = $this->createMock(LocationService::class);
        $locationServiceMock->expects($this->once())
            ->method('loadLocation')
            ->with(42)
            ->will(
                $this->returnValue(
                    $this->getLocationMock(['parentLocationId' => $parentLocationId])
                )
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getLocationService')
            ->will($this->returnValue($locationServiceMock));
        $repository
            ->expects($this->any())
            ->method('getPermissionResolver')
            ->will($this->returnValue($this->getPermissionResolverMock()));

        return $repository;
    }
}

class_alias(ParentLocationTest::class, 'eZ\Publish\Core\MVC\Symfony\Matcher\Tests\ContentBased\Id\ParentLocationTest');
