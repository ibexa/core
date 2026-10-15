<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentTypeGroup as ContentTypeGroupIdMatcher;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(ContentTypeGroupIdMatcher::class)]
#[CoversClass(MultipleValued::class)]
class ContentTypeGroupTest extends BaseTestCase
{
    /** @var ContentTypeGroupIdMatcher */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ContentTypeGroupIdMatcher();
    }

    /**
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchLocationProvider')]
    public function testMatchLocation(
        $matchingConfig,
        int $contentTypeGroupId,
        $expectedResult
    ): void {
        $this->matcher->setRepository($this->generateRepositoryMockForContentTypeGroupId($contentTypeGroupId));
        $this->matcher->setMatchingConfig($matchingConfig);

        $result = $this->matcher->matchLocation($this->generateLocationMock());

        self::assertSame(
            $expectedResult,
            $result
        );
    }

    /**
     * @return array<mixed>
     */
    public static function matchLocationProvider(): array
    {
        $data = [];

        $data[] = [
            123,
            123,
            true,
        ];

        $data[] = [
            123,
            456,
            false,
        ];

        $data[] = [
            [123, 789],
            456,
            false,
        ];

        $data[] = [
            [123, 789],
            789,
            true,
        ];

        return $data;
    }

    /**
     * Generates a Location mock.
     *
     * @return MockObject
     */
    private function generateLocationMock()
    {
        $location = $this->getLocationMock();
        $location
            ->expects($this->any())
            ->method('getContentInfo')
            ->will(
                $this->returnValue(
                    $this->getContentInfoMock(['contentTypeId' => 42])
                )
            );

        return $location;
    }

    /**
     * @param int|int[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchContentInfoProvider')]
    public function testMatchContentInfo(
        $matchingConfig,
        int $contentTypeGroupId,
        $expectedResult
    ): void {
        $this->matcher->setRepository($this->generateRepositoryMockForContentTypeGroupId($contentTypeGroupId));
        $this->matcher->setMatchingConfig($matchingConfig);

        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock(['contentTypeId' => 42]))
        );
    }

    /**
     * @return array<mixed>
     */
    public static function matchContentInfoProvider(): array
    {
        $data = [];

        $data[] = [
            123,
            123,
            true,
        ];

        $data[] = [
            123,
            456,
            false,
        ];

        $data[] = [
            [123, 789],
            456,
            false,
        ];

        $data[] = [
            [123, 789],
            789,
            true,
        ];

        return $data;
    }

    /**
     * Returns a Repository mock configured to return the appropriate Location object with given parent location Id.
     *
     * @param int $contentTypeGroupId
     *
     * @return MockObject
     */
    private function generateRepositoryMockForContentTypeGroupId($contentTypeGroupId)
    {
        $contentTypeServiceMock = $this->createMock(ContentTypeService::class);
        $contentTypeMock = $this->getMockForAbstractClass(ContentType::class);
        $contentTypeServiceMock->expects($this->once())
            ->method('loadContentType')
            ->with(42)
            ->will($this->returnValue($contentTypeMock));
        $contentTypeMock->expects($this->once())
            ->method('getContentTypeGroups')
            ->will(
                $this->returnValue(
                    [
                        // First a group that will never match, then the right group.
                        // This ensures to test even if the content type belongs to several groups at once.
                        $this->createContentTypeGroupWithId(-1),
                        $this->createContentTypeGroupWithId($contentTypeGroupId),
                    ]
                )
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getContentTypeService')
            ->will($this->returnValue($contentTypeServiceMock));

        return $repository;
    }

    private function createContentTypeGroupWithId(int $id): ContentTypeGroup
    {
        return $this
            ->getMockBuilder(ContentTypeGroup::class)
            ->setConstructorArgs([['id' => $id]])
            ->getMockForAbstractClass();
    }
}
