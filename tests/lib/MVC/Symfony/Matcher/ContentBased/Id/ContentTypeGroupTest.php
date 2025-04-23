<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Id;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentTypeGroup as ContentTypeGroupIdMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Id\ContentTypeGroup
 */
class ContentTypeGroupTest extends BaseTestCase
{
    private ContentTypeGroupIdMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ContentTypeGroupIdMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @param int|int[] $matchingConfig
     */
    public function testMatchLocation(array|int $matchingConfig, Repository $repository, bool $expectedResult): void
    {
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);

        self::assertSame(
            $expectedResult,
            $this->matcher->matchLocation($this->generateLocationMock())
        );
    }

    /**
     * @phpstan-return iterable<array{int|array<int>, \Ibexa\Contracts\Core\Repository\Repository, bool}>
     */
    public function matchLocationProvider(): iterable
    {
        return $this->getDataForMatchProvider();
    }

    /**
     * Generates a Location mock.
     */
    private function generateLocationMock(): Location & MockObject
    {
        $location = $this->getLocationMock();
        $location
            ->method('getContentInfo')
            ->willReturn(
                $this->getContentInfoMock(['contentTypeId' => 42])
            );

        return $location;
    }

    /**
     * @dataProvider matchContentInfoProvider
     *
     * @param int|int[] $matchingConfig
     */
    public function testMatchContentInfo(array|int $matchingConfig, Repository $repository, bool $expectedResult): void
    {
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);

        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo($this->getContentInfoMock(['contentTypeId' => 42]))
        );
    }

    /**
     * @phpstan-return iterable<array{int|array<int>, \Ibexa\Contracts\Core\Repository\Repository, bool}>
     */
    public function matchContentInfoProvider(): iterable
    {
        return $this->getDataForMatchProvider();
    }

    /**
     * Returns a Repository mock configured to return the appropriate Location object with the given parent location Id.
     */
    private function generateRepositoryMockForContentTypeGroupId(int $contentTypeGroupId): Repository & MockObject
    {
        $contentTypeServiceMock = $this->createMock(ContentTypeService::class);
        $contentTypeMock = $this->getMockForAbstractClass(ContentType::class);
        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(42)
            ->willReturn($contentTypeMock);
        $contentTypeMock->expects(self::once())
            ->method('getContentTypeGroups')
            ->willReturn(
                [
                    // First a group that will never match, then the right group.
                    // This ensures testing it even if the content type belongs to several groups at once.
                    $this->getMockForAbstractClass(ContentTypeGroup::class),
                    $this
                        ->getMockBuilder(ContentTypeGroup::class)
                        ->setConstructorArgs([['id' => $contentTypeGroupId]])
                        ->getMockForAbstractClass(),
                ]
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::once())
            ->method('getContentTypeService')
            ->willReturn($contentTypeServiceMock);

        return $repository;
    }

    /**
     * @return iterable<array{
     *   int|array<int>,
     *   \Ibexa\Contracts\Core\Repository\Repository,
     *   bool
     * }>
     */
    private function getDataForMatchProvider(): iterable
    {
        yield [
            123,
            $this->generateRepositoryMockForContentTypeGroupId(123),
            true,
        ];

        yield [
            123,
            $this->generateRepositoryMockForContentTypeGroupId(456),
            false,
        ];

        yield [
            [123, 789],
            $this->generateRepositoryMockForContentTypeGroupId(456),
            false,
        ];

        yield [
            [123, 789],
            $this->generateRepositoryMockForContentTypeGroupId(789),
            true,
        ];
    }
}
