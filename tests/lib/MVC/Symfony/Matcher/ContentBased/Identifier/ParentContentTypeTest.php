<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\Identifier;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ParentContentType;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ParentContentType as ParentContentTypeMatcher;
use Ibexa\Tests\Core\MVC\Symfony\Matcher\ContentBased\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

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
     * Returns a Repository mock configured to return the appropriate Section object with given section identifier.
     *
     * @param string $contentTypeIdentifier
     *
     * @return MockObject
     */
    private function generateRepositoryMockForContentTypeIdentifier($contentTypeIdentifier)
    {
        $parentContentInfo = $this->getContentInfoMock([
            'mainLocationId' => self::EXAMPLE_LOCATION_ID,
            'contentTypeId' => 42,
        ]);
        $parentLocation = $this->getLocationMock([
            'parentLocationId' => self::EXAMPLE_PARENT_LOCATION_ID,
        ]);
        $parentLocation->expects(self::once())
            ->method('getContentInfo')
            ->will(
                self::returnValue($parentContentInfo)
            );

        $locationServiceMock = $this->createMock(LocationService::class);
        $locationServiceMock->expects(self::atLeastOnce())
            ->method('loadLocation')
            ->will(
                self::returnValue($parentLocation)
            );
        // The following is used in the case of a match by contentInfo
        $locationServiceMock->expects(self::any())
            ->method('loadLocation')
            ->will(
                self::returnValue($this->getLocationMock())
            );

        $contentTypeServiceMock = $this->createMock(ContentTypeService::class);
        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(42)
            ->will(
                self::returnValue(
                    $this
                        ->getMockBuilder(ContentType::class)
                        ->setConstructorArgs(
                            [
                                ['identifier' => $contentTypeIdentifier],
                            ]
                        )
                        ->getMockForAbstractClass()
                )
            );

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::any())
            ->method('getLocationService')
            ->will(self::returnValue($locationServiceMock));
        $repository
            ->expects(self::once())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));
        $repository
            ->expects(self::any())
            ->method('getPermissionResolver')
            ->will(self::returnValue($this->getPermissionResolverMock()));

        return $repository;
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ParentContentType::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     * @covers \Ibexa\Core\MVC\RepositoryAware::setRepository
     *
     * @param string|string[] $matchingConfig
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
        self::assertSame(
            $expectedResult,
            $this->matcher->matchLocation($this->getLocationMock([
                'parentLocationId' => self::EXAMPLE_LOCATION_ID,
            ]))
        );
    }

    public function matchLocationProvider()
    {
        return [
            [
                'foo',
                $this->generateRepositoryMockForContentTypeIdentifier('foo'),
                true,
            ],
            [
                'foo',
                $this->generateRepositoryMockForContentTypeIdentifier('bar'),
                false,
            ],
            [
                ['foo', 'baz'],
                $this->generateRepositoryMockForContentTypeIdentifier('bar'),
                false,
            ],
            [
                ['foo', 'baz'],
                $this->generateRepositoryMockForContentTypeIdentifier('baz'),
                true,
            ],
        ];
    }

    /**
     * @dataProvider matchLocationProvider
     *
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ParentContentType::matchLocation
     * @covers \Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued::setMatchingConfig
     * @covers \Ibexa\Core\MVC\RepositoryAware::setRepository
     *
     * @param string|string[] $matchingConfig
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
            $this->matcher->matchContentInfo($this->getContentInfoMock([
                'mainLocationId' => self::EXAMPLE_LOCATION_ID,
            ]))
        );
    }
}
