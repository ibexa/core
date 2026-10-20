<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Core\MVC\RepositoryAware;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ParentContentType;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ParentContentType as ParentContentTypeMatcher;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(ParentContentTypeMatcher::class)]
#[CoversClass(MultipleValued::class)]
#[CoversClass(RepositoryAware::class)]
class ParentContentTypeTest extends BaseTestCase
{
    private const EXAMPLE_LOCATION_ID = 54;
    private const EXAMPLE_PARENT_LOCATION_ID = 2;

    /** @var ParentContentType */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ParentContentTypeMatcher();
    }

    /**
     * Returns a Repository mock configured to return the appropriate ContentType object with given id.
     *
     * @param int $contentTypeId
     *
     * @return MockObject
     */
    private function generateRepositoryMockForContentTypeId($contentTypeId)
    {
        $parentContentInfo = $this->getContentInfoMock([
            'contentTypeId' => $contentTypeId,
            'mainLocationId' => self::EXAMPLE_LOCATION_ID,
        ]);

        $parentLocation = $this->getLocationMock([
            'parentLocationId' => self::EXAMPLE_PARENT_LOCATION_ID,
        ]);
        $parentLocation->expects($this->once())
            ->method('getContentInfo')
            ->will(
                $this->returnValue($parentContentInfo)
            );

        $locationServiceMock = $this->createMock(LocationService::class);

        $locationServiceMock->expects($this->atLeastOnce())
            ->method('loadLocation')
            ->will(
                $this->returnValue($parentLocation)
            );
        // The following is used in the case of a match by contentInfo
        $locationServiceMock->expects($this->any())
            ->method('loadLocation')
            ->will(
                $this->returnValue($this->getLocationMock())
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects($this->any())
            ->method('getLocationService')
            ->will($this->returnValue($locationServiceMock));
        $repository
            ->expects($this->any())
            ->method('getPermissionResolver')
            ->will($this->returnValue($this->getPermissionResolverMock()));

        return $repository;
    }

    /**
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchLocationProvider')]
    public function testMatchLocation(
        $matchingConfig,
        int $contentTypeId,
        $expectedResult
    ): void {
        $this->matcher->setRepository($this->generateRepositoryMockForContentTypeId($contentTypeId));
        $this->matcher->setMatchingConfig($matchingConfig);
        self::assertSame(
            $expectedResult,
            $this->matcher->matchLocation($this->getLocationMock(['parentLocationId' => self::EXAMPLE_LOCATION_ID]))
        );
    }

    /**
     * @return array<mixed>
     */
    public static function matchLocationProvider(): array
    {
        return [
            [
                123,
                123,
                true,
            ],
            [
                123,
                456,
                false,
            ],
            [
                [123, 789],
                456,
                false,
            ],
            [
                [123, 789],
                789,
                true,
            ],
        ];
    }

    /**
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchLocationProvider')]
    public function testMatchContentInfo(
        $matchingConfig,
        int $contentTypeId,
        $expectedResult
    ): void {
        $this->matcher->setRepository($this->generateRepositoryMockForContentTypeId($contentTypeId));
        $this->matcher->setMatchingConfig($matchingConfig);

        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock([
                'mainLocationId' => self::EXAMPLE_LOCATION_ID,
            ]))
        );
    }
}
