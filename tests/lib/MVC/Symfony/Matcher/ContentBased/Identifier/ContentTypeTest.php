<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Identifier;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ContentType as ContentTypeIdentifierMatcher;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ContentTypeIdentifierMatcher::class)]
#[CoversClass(MultipleValued::class)]
class ContentTypeTest extends BaseTestCase
{
    /** @var \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ContentType */
    private $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ContentTypeIdentifierMatcher();
    }

    /**
     * @param string|string[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchLocationProvider')]
    public function testMatchLocation($matchingConfig, string $contentTypeIdentifier, $expectedResult): void
    {
        $this->matcher->setRepository($this->generateRepositoryMockForContentTypeIdentifier($contentTypeIdentifier));
        $this->matcher->setMatchingConfig($matchingConfig);

        self::assertSame(
            $expectedResult,
            $this->matcher->matchLocation($this->generateLocationMock())
        );
    }

    /**
     * @return array<mixed>
     */
    public static function matchLocationProvider(): array
    {
        $data = [];

        $data[] = [
            'foo',
            'foo',
            true,
        ];

        $data[] = [
            'foo',
            'bar',
            false,
        ];

        $data[] = [
            ['foo', 'baz'],
            'bar',
            false,
        ];

        $data[] = [
            ['foo', 'baz'],
            'baz',
            true,
        ];

        return $data;
    }

    /**
     * Generates a Location object in respect of a given content type identifier.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
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
     * @param string|string[] $matchingConfig
     * @param bool $expectedResult
     */
    #[DataProvider('matchContentInfoProvider')]
    public function testMatchContentInfo($matchingConfig, string $contentTypeIdentifier, $expectedResult): void
    {
        $this->matcher->setRepository($this->generateRepositoryMockForContentTypeIdentifier($contentTypeIdentifier));
        $this->matcher->setMatchingConfig($matchingConfig);

        self::assertSame(
            $expectedResult,
            $this->matcher->matchContentInfo(
                $this->getContentInfoMock(['contentTypeId' => 42])
            )
        );
    }

    /**
     * @return array<mixed>
     */
    public static function matchContentInfoProvider(): array
    {
        $data = [];

        $data[] = [
            'foo',
            'foo',
            true,
        ];

        $data[] = [
            'foo',
            'bar',
            false,
        ];

        $data[] = [
            ['foo', 'baz'],
            'bar',
            false,
        ];

        $data[] = [
            ['foo', 'baz'],
            'baz',
            true,
        ];

        return $data;
    }

    /**
     * Returns a Repository mock configured to return the appropriate ContentType object with given identifier.
     *
     * @param int $contentTypeIdentifier
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function generateRepositoryMockForContentTypeIdentifier($contentTypeIdentifier)
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
            ->will(
                self::returnValue($contentTypeMock)
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::any())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));

        return $repository;
    }
}
