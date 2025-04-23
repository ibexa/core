<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Identifier;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ContentType as ContentTypeIdentifierMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ContentType
 */
class ContentTypeTest extends BaseTestCase
{
    private ContentTypeIdentifierMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ContentTypeIdentifierMatcher();
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @param string|string[] $matchingConfig
     */
    public function testMatchLocation(array|string $matchingConfig, Repository $repository, bool $expectedResult): void
    {
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);

        self::assertSame(
            $expectedResult,
            $this->matcher->matchLocation($this->generateLocationMock())
        );
    }

    /**
     * @phpstan-return iterable<array{string|string[], \Ibexa\Contracts\Core\Repository\Repository, bool}>
     */
    public function getDataForMatchProvider(): iterable
    {
        yield [
            'foo',
            $this->generateRepositoryMockForContentTypeIdentifier('foo'),
            true,
        ];

        yield [
            'foo',
            $this->generateRepositoryMockForContentTypeIdentifier('bar'),
            false,
        ];

        yield [
            ['foo', 'baz'],
            $this->generateRepositoryMockForContentTypeIdentifier('bar'),
            false,
        ];

        yield [
            ['foo', 'baz'],
            $this->generateRepositoryMockForContentTypeIdentifier('baz'),
            true,
        ];
    }

    /**
     * @phpstan-return iterable<array{string|string[], \Ibexa\Contracts\Core\Repository\Repository, bool}>
     */
    public function matchLocationProvider(): iterable
    {
        return $this->getDataForMatchProvider();
    }

    /**
     * Generates a Location object in respect of a given content type identifier.
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
     * @param string|string[] $matchingConfig
     */
    public function testMatchContentInfo(array|string $matchingConfig, Repository $repository, bool $expectedResult): void
    {
        $this->matcher->setRepository($repository);
        $this->matcher->setMatchingConfig($matchingConfig);

        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo(
                $this->getContentInfoMock(['contentTypeId' => 42])
            )
        );
    }

    /**
     * @phpstan-return iterable<array{string|string[], \Ibexa\Contracts\Core\Repository\Repository, bool}>
     */
    public function matchContentInfoProvider(): iterable
    {
        return $this->getDataForMatchProvider();
    }

    /**
     * Returns a Repository mock configured to return the appropriate ContentType object with given identifier.
     */
    private function generateRepositoryMockForContentTypeIdentifier(string $contentTypeIdentifier): Repository & MockObject
    {
        $contentTypeMock = $this
            ->getMockBuilder(ContentType::class)
            ->setConstructorArgs(
                [['identifier' => $contentTypeIdentifier]]
            )
            ->getMockForAbstractClass();
        $contentTypeServiceMock = $this->createMock(ContentTypeService::class);
        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(42)
            ->willReturn(
                $contentTypeMock
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->method('getContentTypeService')
            ->willReturn($contentTypeServiceMock);

        return $repository;
    }
}
