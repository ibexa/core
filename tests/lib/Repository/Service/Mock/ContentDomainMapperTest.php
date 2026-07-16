<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use DateTime;
use DateTimeImmutable;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo as SPIContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Field as PersistenceContentField;
use Ibexa\Contracts\Core\Persistence\Content\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Type as PersistenceContentType;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo as SPIVersionInfo;
use Ibexa\Contracts\Core\Repository\Exceptions\Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\Mapper\ContentDomainMapper;
use Ibexa\Core\Repository\ProxyFactory\ProxyDomainMapperInterface;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinitionCollection;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * @covers \Ibexa\Core\Repository\Mapper\ContentDomainMapper
 */
final class ContentDomainMapperTest extends BaseServiceMockTest
{
    use ExpectDeprecationTrait;

    private const EXAMPLE_CONTENT_INFO_ID = 1;
    private const EXAMPLE_CONTENT_TYPE_ID = 1;
    private const EXAMPLE_NAME = 'Example';
    private const EXAMPLE_SECTION_ID = 1;
    private const EXAMPLE_MAIN_LOCATION_ID = 1;
    private const EXAMPLE_MAIN_LANGUAGE_CODE = 'ger-DE';
    private const EXAMPLE_OWNER_ID = 1;
    private const EXAMPLE_INITIAL_LANGUAGE_CODE = 'eng-GB';
    private const EXAMPLE_CREATOR_ID = 23;
    private const int EXAMPLE_VERSION_INFO_ID = 12;

    /**
     * @dataProvider providerForBuildVersionInfo
     */
    public function testBuildVersionInfo(SPIVersionInfo $spiVersionInfo)
    {
        $languageHandlerMock = $this->getLanguageHandlerMock();
        $languageHandlerMock->expects(self::never())->method('load');

        $versionInfo = $this->getContentDomainMapper()->buildVersionInfoDomainObject($spiVersionInfo);

        self::assertInstanceOf(APIVersionInfo::class, $versionInfo);
    }

    public function testBuildLocationWithContentForRootLocation()
    {
        $spiRootLocation = new Location([
            'id' => 1,
            'parentId' => 1,
            'priority' => 0,
            'hidden' => false,
            'invisible' => false,
            'remoteId' => 'a0b21e72f98a4637d169c4144edf39c3',
            'pathString' => '/1',
            'depth' => 0,
            'sortField' => Location::SORT_FIELD_PRIORITY,
            'sortOrder' => Location::SORT_ORDER_ASC,
        ]);
        $apiRootLocation = $this->getContentDomainMapper()->buildLocationWithContent($spiRootLocation, null);

        $legacyDateTime = new DateTime();
        $legacyDateTime->setTimestamp(1030968000);

        $expectedContentInfo = new ContentInfo([
            'id' => 0,
            'name' => 'Top Level Nodes',
            'sectionId' => 1,
            'mainLocationId' => 1,
            'contentTypeId' => 1,
            'currentVersionNo' => 1,
            'published' => 1,
            'ownerId' => 14,
            'modificationDate' => $legacyDateTime,
            'publishedDate' => $legacyDateTime,
            'alwaysAvailable' => 1,
            'remoteId' => 'IBEXA_ROOT_385b2cd4737a459c999ba4b7595a0016',
            'mainLanguageCode' => 'eng-GB',
            'isHidden' => false,
        ]);

        $expectedContent = new Content([
            'versionInfo' => new VersionInfo([
                'names' => [
                    $expectedContentInfo->mainLanguageCode => $expectedContentInfo->name,
                ],
                'contentInfo' => $expectedContentInfo,
                'versionNo' => $expectedContentInfo->currentVersionNo,
                'modificationDate' => $expectedContentInfo->modificationDate,
                'creationDate' => $expectedContentInfo->modificationDate,
                'creatorId' => $expectedContentInfo->ownerId,
            ]),
        ]);

        self::assertInstanceOf(APILocation::class, $apiRootLocation);
        self::assertEquals($spiRootLocation->id, $apiRootLocation->id);
        self::assertEquals($expectedContentInfo->id, $apiRootLocation->getContentInfo()->id);
        self::assertEquals($expectedContent, $apiRootLocation->getContent());
    }

    public function testBuildLocationWithContentThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'$content\' is invalid: Location 2 has missing Content');

        $nonRootLocation = new Location(['id' => 2, 'parentId' => 1]);

        $this->getContentDomainMapper()->buildLocationWithContent($nonRootLocation, null);
    }

    public function testBuildLocationWithContentIsAlignedWithBuildLocation()
    {
        $spiRootLocation = new Location([
            'id' => 1,
            'parentId' => 1,
            'priority' => 0,
            'invisible' => false,
            'hidden' => false,
            'remoteId' => 'a0b21e72f98a4637d169c4144edf39c3',
            'pathString' => '/1',
            'depth' => 0,
            'sortField' => Location::SORT_FIELD_PRIORITY,
            'sortOrder' => Location::SORT_ORDER_ASC,
        ]);

        self::assertEquals(
            $this->getContentDomainMapper()->buildLocationWithContent($spiRootLocation, null),
            $this->getContentDomainMapper()->buildLocation($spiRootLocation)
        );
    }

    /**
     * @throws Exception
     *
     * @group legacy
     */
    public function testBuildDomainFieldsDeprecatedBehavior(): void
    {
        $persistenceFields = [new PersistenceContentField()];
        $persistenceContentType = $this->createMock(PersistenceContentType::class);
        $apiContentTypeMock = $this->createMock(ContentType::class);
        $apiContentTypeMock->method('getFieldDefinitions')->willReturn(new FieldDefinitionCollection());
        $this
            ->getContentTypeDomainMapperMock()
            ->method('buildContentTypeDomainObject')
            ->with($persistenceContentType, [])->willReturn($apiContentTypeMock)
        ;

        $this->expectDeprecation(
            'Since ibexa/core 4.6: Passing Ibexa\Contracts\Core\Persistence\Content\Type instead of ' .
            'Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType as 2nd argument of ' .
            'Ibexa\Core\Repository\Mapper\ContentDomainMapper::buildDomainFields() method is deprecated and will cause ' .
            'a fatal error in 5.0. Build Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType using ' .
            'Ibexa\Core\Repository\Mapper\ContentTypeDomainMapper::buildContentTypeDomainObject prior passing it to the method'
        );

        $this->getContentDomainMapper()->buildDomainFields($persistenceFields, $persistenceContentType);
    }

    public function providerForBuildVersionInfo()
    {
        $properties = [
            'id' => self::EXAMPLE_VERSION_INFO_ID,
            'versionNo' => 1,
            'contentInfo' => new SPIContentInfo([
                'id' => self::EXAMPLE_CONTENT_INFO_ID,
                'name' => self::EXAMPLE_NAME,
                'contentTypeId' => self::EXAMPLE_CONTENT_TYPE_ID,
                'sectionId' => self::EXAMPLE_SECTION_ID,
                'mainLocationId' => self::EXAMPLE_MAIN_LOCATION_ID,
                'mainLanguageCode' => self::EXAMPLE_MAIN_LANGUAGE_CODE,
                'ownerId' => self::EXAMPLE_OWNER_ID,
                'currentVersionNo' => 1,
                'modificationDate' => (new DateTimeImmutable('2025-06-01 00:00:00'))->getTimestamp(),
                'publicationDate' => (new DateTimeImmutable('2025-06-01 00:00:00'))->getTimestamp(),
                'alwaysAvailable' => false,
                'remoteId' => 'a0b21e72f98a4637d169c4144edf39c3',
            ]),
            'creatorId' => self::EXAMPLE_CREATOR_ID,
            'initialLanguageCode' => self::EXAMPLE_INITIAL_LANGUAGE_CODE,
        ];

        return [
            [
                new SPIVersionInfo(
                    $properties + [
                        'status' => 44,
                    ]
                ),
            ],
            [
                new SPIVersionInfo(
                    $properties + [
                        'status' => SPIVersionInfo::STATUS_DRAFT,
                    ]
                ),
            ],
            [
                new SPIVersionInfo(
                    $properties + [
                        'status' => SPIVersionInfo::STATUS_PENDING,
                    ]
                ),
            ],
            [
                new SPIVersionInfo(
                    $properties + [
                        'status' => SPIVersionInfo::STATUS_ARCHIVED,
                        'languageCodes' => ['eng-GB', 'nor-NB', 'fre-FR'],
                    ]
                ),
            ],
            [
                new SPIVersionInfo(
                    $properties + [
                        'status' => SPIVersionInfo::STATUS_PUBLISHED,
                    ]
                ),
            ],
        ];
    }

    public function providerForBuildLocationDomainObjectsOnSearchResult()
    {
        $properties = [
            'name' => self::EXAMPLE_NAME,
            'contentTypeId' => self::EXAMPLE_CONTENT_TYPE_ID,
            'sectionId' => self::EXAMPLE_SECTION_ID,
            'mainLocationId' => self::EXAMPLE_MAIN_LOCATION_ID,
            'mainLanguageCode' => self::EXAMPLE_MAIN_LANGUAGE_CODE,
            'ownerId' => self::EXAMPLE_OWNER_ID,
            'currentVersionNo' => 1,
            'modificationDate' => (new DateTimeImmutable('2025-06-01 00:00:00'))->getTimestamp(),
            'publicationDate' => (new DateTimeImmutable('2025-06-01 00:00:00'))->getTimestamp(),
            'alwaysAvailable' => true,
            'remoteId' => 'a0b21e72f98a4637d169c4144edf39c3',
        ];

        $locationHits = [
            new Location([
                'id' => 21,
                'contentId' => 32,
                'parentId' => 1,
                'priority' => 0,
                'invisible' => false,
                'hidden' => false,
                'remoteId' => 'a0b21e72f98a4637d169c4144edf39c3',
                'pathString' => '/1/21',
                'depth' => 1,
                'sortField' => Location::SORT_FIELD_PRIORITY,
                'sortOrder' => Location::SORT_ORDER_ASC,
            ]),
            new Location([
                'id' => 22,
                'contentId' => 33,
                'parentId' => 1,
                'priority' => 0,
                'invisible' => false,
                'hidden' => false,
                'remoteId' => 'b0b21e72f98a4637d169c4144edf39c3',
                'pathString' => '/1/22',
                'depth' => 1,
                'sortField' => Location::SORT_FIELD_PRIORITY,
                'sortOrder' => Location::SORT_ORDER_ASC,
            ]),
        ];

        return [
            [
                $locationHits,
                [32, 33],
                [],
                [
                    32 => new SPIContentInfo($properties + ['id' => 32]),
                    33 => new SPIContentInfo($properties + ['id' => 33]),
                ],
                0,
            ],
            [
                $locationHits,
                [32, 33],
                ['languages' => ['eng-GB']],
                [
                    32 => new SPIContentInfo($properties + ['id' => 32]),
                ],
                1,
            ],
            [
                $locationHits,
                [32, 33],
                ['languages' => ['eng-GB']],
                [],
                2,
            ],
        ];
    }

    /**
     * @dataProvider providerForBuildLocationDomainObjectsOnSearchResult
     *
     * @param array $locationHits
     * @param array $contentIds
     * @param array $languageFilter
     * @param array $contentInfoList
     * @param int $missing
     */
    public function testBuildLocationDomainObjectsOnSearchResult(
        array $locationHits,
        array $contentIds,
        array $languageFilter,
        array $contentInfoList,
        int $missing
    ) {
        $contentHandlerMock = $this->getContentHandlerMock();
        $contentHandlerMock
            ->expects(self::once())
            ->method('loadContentInfoList')
            ->with($contentIds)
            ->willReturn($contentInfoList);

        $result = new SearchResult(['totalCount' => 10]);
        foreach ($locationHits as $locationHit) {
            $result->searchHits[] = new SearchHit(['valueObject' => $locationHit]);
        }

        $spiResult = clone $result;
        $missingLocations = $this->getContentDomainMapper()->buildLocationDomainObjectsOnSearchResult(
            $result,
            $languageFilter
        );
        self::assertIsArray($missingLocations);

        if (!$missing) {
            self::assertEmpty($missingLocations);
        } else {
            self::assertNotEmpty($missingLocations);
        }

        self::assertCount($missing, $missingLocations);
        self::assertEquals($spiResult->totalCount - $missing, $result->totalCount);
        self::assertCount(count($spiResult->searchHits) - $missing, $result->searchHits);
    }

    /**
     * Returns ContentDomainMapper.
     *
     * @return ContentDomainMapper
     */
    protected function getContentDomainMapper(): ContentDomainMapper
    {
        return new ContentDomainMapper(
            $this->getContentHandlerMock(),
            $this->getPersistenceMockHandler('Content\\Location\\Handler'),
            $this->getTypeHandlerMock(),
            $this->getContentTypeDomainMapperMock(),
            $this->getLanguageHandlerMock(),
            $this->getFieldTypeRegistryMock(),
            $this->getThumbnailStrategy(),
            $this->getLoggerMock(),
            $this->getProxyFactoryMock()
        );
    }

    /**
     * @return Handler|MockObject
     */
    protected function getContentHandlerMock()
    {
        return $this->getPersistenceMockHandler('Content\\Handler');
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language\Handler|MockObject
     */
    protected function getLanguageHandlerMock()
    {
        return $this->getPersistenceMockHandler('Content\\Language\\Handler');
    }

    /**
     * @return PersistenceContentType\Handler|MockObject
     */
    protected function getTypeHandlerMock()
    {
        return $this->getPersistenceMockHandler('Content\\Type\\Handler');
    }

    protected function getProxyFactoryMock(): ProxyDomainMapperInterface
    {
        return $this->createMock(ProxyDomainMapperInterface::class);
    }

    protected function getLoggerMock(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }
}
