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
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

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
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentTypeGroup::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     *
     * @param int|int[] $matchingConfig
     * @param Repository $repository
     * @param bool $expectedResult
     */
    public function testMatchLocation(
        $matchingConfig,
        Repository $repository,
        $expectedResult
    ) {
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);

        $result = $this->matcher->matchLocation($this->generateLocationMock());

        self::assertSame(
            $expectedResult,
            $result
        );
    }

    public function matchLocationProvider()
    {
        $data = [];

        $data[] = [
            123,
            $this->generateRepositoryMockForContentTypeGroupId(123),
            true,
        ];

        $data[] = [
            123,
            $this->generateRepositoryMockForContentTypeGroupId(456),
            false,
        ];

        $data[] = [
            [123, 789],
            $this->generateRepositoryMockForContentTypeGroupId(456),
            false,
        ];

        $data[] = [
            [123, 789],
            $this->generateRepositoryMockForContentTypeGroupId(789),
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
            ->expects(self::any())
            ->method('getContentInfo')
            ->will(
                self::returnValue(
                    $this->getContentInfoMock(['contentTypeId' => 42])
                )
            );

        return $location;
    }

    /**
     * @dataProvider matchContentInfoProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentTypeGroup::matchContentInfo
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
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

        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock(['contentTypeId' => 42]))
        );
    }

    public function matchContentInfoProvider()
    {
        $data = [];

        $data[] = [
            123,
            $this->generateRepositoryMockForContentTypeGroupId(123),
            true,
        ];

        $data[] = [
            123,
            $this->generateRepositoryMockForContentTypeGroupId(456),
            false,
        ];

        $data[] = [
            [123, 789],
            $this->generateRepositoryMockForContentTypeGroupId(456),
            false,
        ];

        $data[] = [
            [123, 789],
            $this->generateRepositoryMockForContentTypeGroupId(789),
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
        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(42)
            ->will(self::returnValue($contentTypeMock));
        $contentTypeMock->expects(self::once())
            ->method('getContentTypeGroups')
            ->will(
                self::returnValue(
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
            ->expects(self::once())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));

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
