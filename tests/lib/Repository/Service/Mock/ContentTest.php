<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Exception;
use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Contracts\Core\FieldType\FieldType as SPIFieldType;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Limitation\Target\DestinationLocation;
use Ibexa\Contracts\Core\Persistence\Content as SPIContent;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo as SPIContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct as SPIContentCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Field as SPIField;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct as SPIMetadataUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState as SPIObjectState;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group as SPIObjectStateGroup;
use Ibexa\Contracts\Core\Persistence\Content\UpdateStruct as SPIContentUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo as SPIVersionInfo;
use Ibexa\Contracts\Core\Repository\ContentTypeService as APIContentTypeService;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService as APILocationService;
use Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct as APIContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo as APIContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType as APIContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition as APIFieldDefinition;
use Ibexa\Core\Base\Exceptions\ContentFieldValidationException;
use Ibexa\Core\Base\Exceptions\ContentValidationException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value;
use Ibexa\Core\Repository\Collector\ContentCollector;
use Ibexa\Core\Repository\ContentService;
use Ibexa\Core\Repository\Helper\RelationProcessor;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinitionCollection;
use Ibexa\Core\Repository\Values\User\UserReference;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Mock test case for Content service.
 */
class ContentTest extends BaseServiceMockTest
{
    private const string EMPTY_FIELD_VALUE = 'empty';
    private const string EXAMPLE_FIELD_TYPE_IDENTIFIER = 'field_type_identifier';

    private const int EXAMPLE_FIELD_DEFINITION_ID = 1;

    private const int EXAMPLE_FIELD_DEFINITION_ID_A = 1;
    private const int EXAMPLE_FIELD_DEFINITION_ID_B = 2;
    private const int EXAMPLE_FIELD_DEFINITION_ID_C = 3;
    private const int EXAMPLE_FIELD_DEFINITION_ID_D = 4;

    /**
     * Test for the __construct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::__construct
     */
    public function testConstructor(): void
    {
        $repositoryMock = $this->getRepositoryMock();
        /** @var \Ibexa\Contracts\Core\Persistence\Handler $persistenceHandlerMock */
        $persistenceHandlerMock = $this->getPersistenceMockHandler('Handler');
        $contentDomainMapperMock = $this->getContentDomainMapperMock();
        $relationProcessorMock = $this->getRelationProcessorMock();
        $nameSchemaServiceMock = $this->getNameSchemaServiceMock();
        $fieldTypeRegistryMock = $this->getFieldTypeRegistryMock();
        $permissionServiceMock = $this->getPermissionServiceMock();
        $contentMapper = $this->getContentMapper();
        $contentValidatorStrategy = $this->getContentValidatorStrategy();
        $contentFilteringHandlerMock = $this->getContentFilteringHandlerMock();
        $settings = [
            'default_version_archive_limit' => 10,
            'remove_archived_versions_on_publish' => true,
        ];

        new ContentService(
            $repositoryMock,
            $persistenceHandlerMock,
            $contentDomainMapperMock,
            $relationProcessorMock,
            $nameSchemaServiceMock,
            $fieldTypeRegistryMock,
            $permissionServiceMock,
            $contentMapper,
            $contentValidatorStrategy,
            $contentFilteringHandlerMock,
            new ContentCollector(),
            $this->createMock(ValidatorInterface::class),
            $settings
        );
    }

    /**
     * Test for the loadVersionInfo() method, of published version.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById
     */
    public function testLoadVersionInfoById()
    {
        $contentServiceMock = $this->getPartlyMockedContentService(['loadContentInfo']);
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $versionInfoMock = $this->createMock(APIVersionInfo::class);
        $permissionResolver = $this->getPermissionResolverMock();

        $versionInfoMock->expects(self::once())
            ->method('isPublished')
            ->willReturn(true);

        $contentServiceMock->expects(self::never())
            ->method('loadContentInfo');

        $contentHandler->expects(self::once())
            ->method('loadVersionInfo')
            ->with(
                self::equalTo(42),
                self::equalTo(null)
            )->will(
                self::returnValue(new SPIVersionInfo())
            );

        $domainMapperMock->expects(self::once())
            ->method('buildVersionInfoDomainObject')
            ->with(new SPIVersionInfo())
            ->will(self::returnValue($versionInfoMock));

        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('read'),
                self::equalTo($versionInfoMock)
            )->will(self::returnValue(true));

        $result = $contentServiceMock->loadVersionInfoById(42);

        self::assertEquals($versionInfoMock, $result);
    }

    /**
     * Test for the loadVersionInfo() method, of a draft.
     *
     * @depends testLoadVersionInfoById
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById
     */
    public function testLoadVersionInfoByIdAndVersionNumber()
    {
        $permissionResolver = $this->getPermissionResolverMock();
        $contentServiceMock = $this->getPartlyMockedContentService(['loadContentInfo']);
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $versionInfoMock = $this->createMock(APIVersionInfo::class);

        $versionInfoMock->expects(self::any())
            ->method('__get')
            ->with('status')
            ->willReturn(APIVersionInfo::STATUS_DRAFT);

        $contentServiceMock->expects(self::never())
            ->method('loadContentInfo');

        $contentHandler->expects(self::once())
            ->method('loadVersionInfo')
            ->with(
                self::equalTo(42),
                self::equalTo(2)
            )->willReturn(new SPIVersionInfo());

        $domainMapperMock->expects(self::once())
            ->method('buildVersionInfoDomainObject')
            ->with(new SPIVersionInfo())
            ->willReturn($versionInfoMock);

        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('versionread'),
                self::equalTo($versionInfoMock)
            )->willReturn(true);

        $result = $contentServiceMock->loadVersionInfoById(42, 2);

        self::assertEquals($versionInfoMock, $result);
    }

    /**
     * Test for the loadVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById
     */
    public function testLoadVersionInfoByIdThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $contentServiceMock = $this->getPartlyMockedContentService(['loadContentInfo']);
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();

        $contentHandler->expects(self::once())
            ->method('loadVersionInfo')
            ->with(
                self::equalTo(42),
                self::equalTo(24)
            )->will(
                self::throwException(
                    new NotFoundException(
                        'Content',
                        [
                            'contentId' => 42,
                            'versionNo' => 24,
                        ]
                    )
                )
            );

        $contentServiceMock->loadVersionInfoById(42, 24);
    }

    /**
     * Test for the loadVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById
     */
    public function testLoadVersionInfoByIdThrowsUnauthorizedExceptionNonPublishedVersion()
    {
        $this->expectException(UnauthorizedException::class);

        $contentServiceMock = $this->getPartlyMockedContentService();
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $versionInfoMock = $this->createMock(APIVersionInfo::class);
        $permissionResolver = $this->getPermissionResolverMock();

        $versionInfoMock->expects(self::any())
            ->method('isPublished')
            ->willReturn(false);

        $contentHandler->expects(self::once())
            ->method('loadVersionInfo')
            ->with(
                self::equalTo(42),
                self::equalTo(24)
            )->will(
                self::returnValue(new SPIVersionInfo())
            );

        $domainMapperMock->expects(self::once())
            ->method('buildVersionInfoDomainObject')
            ->with(new SPIVersionInfo())
            ->will(self::returnValue($versionInfoMock));

        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('versionread'),
                self::equalTo($versionInfoMock)
            )->will(self::returnValue(false));

        $contentServiceMock->loadVersionInfoById(42, 24);
    }

    /**
     * Test for the loadVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById
     */
    public function testLoadVersionInfoByIdPublishedVersion()
    {
        $contentServiceMock = $this->getPartlyMockedContentService();
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $versionInfoMock = $this->createMock(APIVersionInfo::class);
        $permissionResolver = $this->getPermissionResolverMock();

        $versionInfoMock->expects(self::once())
            ->method('isPublished')
            ->willReturn(true);

        $contentHandler->expects(self::once())
            ->method('loadVersionInfo')
            ->with(
                self::equalTo(42),
                self::equalTo(24)
            )->will(
                self::returnValue(new SPIVersionInfo())
            );

        $domainMapperMock->expects(self::once())
            ->method('buildVersionInfoDomainObject')
            ->with(new SPIVersionInfo())
            ->will(self::returnValue($versionInfoMock));

        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('read'),
                self::equalTo($versionInfoMock)
            )->will(self::returnValue(true));

        $result = $contentServiceMock->loadVersionInfoById(42, 24);

        self::assertEquals($versionInfoMock, $result);
    }

    /**
     * Test for the loadVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById
     */
    public function testLoadVersionInfoByIdNonPublishedVersion()
    {
        $contentServiceMock = $this->getPartlyMockedContentService();
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $versionInfoMock = $this->createMock(APIVersionInfo::class);
        $permissionResolver = $this->getPermissionResolverMock();

        $versionInfoMock->expects(self::once())
            ->method('isPublished')
            ->willReturn(false);

        $contentHandler->expects(self::once())
            ->method('loadVersionInfo')
            ->with(
                self::equalTo(42),
                self::equalTo(24)
            )->will(
                self::returnValue(new SPIVersionInfo())
            );

        $domainMapperMock->expects(self::once())
            ->method('buildVersionInfoDomainObject')
            ->with(new SPIVersionInfo())
            ->will(self::returnValue($versionInfoMock));

        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('versionread'),
                self::equalTo($versionInfoMock)
            )->will(self::returnValue(true));

        $result = $contentServiceMock->loadVersionInfoById(42, 24);

        self::assertEquals($versionInfoMock, $result);
    }

    /**
     * Test for the loadVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfo
     *
     * @depends Ibexa\Tests\Core\Repository\Service\Mock\ContentTest::testLoadVersionInfoById
     * @depends Ibexa\Tests\Core\Repository\Service\Mock\ContentTest::testLoadVersionInfoByIdThrowsNotFoundException
     * @depends Ibexa\Tests\Core\Repository\Service\Mock\ContentTest::testLoadVersionInfoByIdThrowsUnauthorizedExceptionNonPublishedVersion
     * @depends Ibexa\Tests\Core\Repository\Service\Mock\ContentTest::testLoadVersionInfoByIdPublishedVersion
     * @depends Ibexa\Tests\Core\Repository\Service\Mock\ContentTest::testLoadVersionInfoByIdNonPublishedVersion
     */
    public function testLoadVersionInfo()
    {
        $expectedResult = $this->createMock(VersionInfo::class);

        $contentServiceMock = $this->getPartlyMockedContentService(
            ['loadVersionInfoById']
        );
        $contentServiceMock->expects(
            self::once()
        )->method(
            'loadVersionInfoById'
        )->with(
            self::equalTo(42),
            self::equalTo(7)
        )->will(
            self::returnValue($expectedResult)
        );

        $result = $contentServiceMock->loadVersionInfo(
            new ContentInfo(['id' => 42]),
            7
        );

        self::assertEquals($expectedResult, $result);
    }

    public function testLoadContent()
    {
        $contentService = $this->getPartlyMockedContentService(['internalLoadContentById']);
        $content = $this->createMock(APIContent::class);
        $versionInfo = $this->createMock(APIVersionInfo::class);
        $permissionResolver = $this->getPermissionResolverMock();

        $content
            ->expects(self::once())
            ->method('getVersionInfo')
            ->will(self::returnValue($versionInfo));
        $versionInfo
            ->expects(self::once())
            ->method('isPublished')
            ->willReturn(true);
        $contentId = 123;
        $contentService
            ->expects(self::once())
            ->method('internalLoadContentById')
            ->with($contentId)
            ->will(self::returnValue($content));

        $permissionResolver
            ->expects(self::once())
            ->method('canUser')
            ->with('content', 'read', $content)
            ->will(self::returnValue(true));

        self::assertSame($content, $contentService->loadContent($contentId));
    }

    public function testLoadContentNonPublished()
    {
        $contentService = $this->getPartlyMockedContentService(['internalLoadContentById']);
        $content = $this->createMock(APIContent::class);
        $versionInfo = $this->createMock(APIVersionInfo::class);
        $permissionResolver = $this->getPermissionResolverMock();

        $content
            ->expects(self::once())
            ->method('getVersionInfo')
            ->will(self::returnValue($versionInfo));
        $contentId = 123;
        $contentService
            ->expects(self::once())
            ->method('internalLoadContentById')
            ->with($contentId)
            ->will(self::returnValue($content));

        $permissionResolver
            ->expects(self::exactly(2))
            ->method('canUser')
            ->will(
                self::returnValueMap(
                    [
                        ['content', 'read', $content, [], true],
                        ['content', 'versionread', $content, [], true],
                    ]
                )
            );

        self::assertSame($content, $contentService->loadContent($contentId));
    }

    public function testLoadContentUnauthorized()
    {
        $this->expectException(UnauthorizedException::class);

        $permissionResolver = $this->getPermissionResolverMock();

        $contentService = $this->getPartlyMockedContentService(['internalLoadContentById']);
        $content = $this->createMock(APIContent::class);
        $contentId = 123;
        $contentService
            ->expects(self::once())
            ->method('internalLoadContentById')
            ->with($contentId)
            ->will(self::returnValue($content));

        $permissionResolver
            ->expects(self::once())
            ->method('canUser')
            ->with('content', 'read', $content)
            ->will(self::returnValue(false));

        $contentService->loadContent($contentId);
    }

    public function testLoadContentNotPublishedStatusUnauthorized()
    {
        $permissionResolver = $this->getPermissionResolverMock();
        $contentService = $this->getPartlyMockedContentService(['internalLoadContentById']);
        $content = $this->createMock(APIContent::class);
        $versionInfo = $this
            ->getMockBuilder(APIVersionInfo::class)
            ->getMockForAbstractClass();
        $content
            ->expects(self::once())
            ->method('getVersionInfo')
            ->will(self::returnValue($versionInfo));
        $contentId = 123;
        $contentService
            ->expects(self::once())
            ->method('internalLoadContentById')
            ->with($contentId)
            ->will(self::returnValue($content));

        $permissionResolver
            ->expects(self::exactly(2))
            ->method('canUser')
            ->will(
                self::returnValueMap(
                    [
                        ['content', 'read', $content, [], true],
                        ['content', 'versionread', $content, [], false],
                    ]
                )
            );

        $this->expectException(UnauthorizedException::class);
        $contentService->loadContent($contentId);
    }

    /**
     * @dataProvider internalLoadContentProviderById
     */
    public function testInternalLoadContentById(int $id, ?array $languages, ?int $versionNo, bool $useAlwaysAvailable): void
    {
        if (!empty($languages) && $useAlwaysAvailable) {
            $spiContentInfo = new SPIContentInfo(['id' => $id, 'alwaysAvailable' => false]);
        } else {
            $spiContentInfo = new SPIContentInfo(['id' => $id]);
        }

        $spiContent = new SPIContent([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo([
                    'id' => 42,
                    'contentTypeId' => 123,
                ]),
            ]),
        ]);

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $contentHandler
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with($id)
            ->will(self::returnValue($spiContentInfo));

        $contentHandler
            ->expects(self::once())
            ->method('load')
            ->with($id, $versionNo, $languages)
            ->willReturn($spiContent);

        $contentService = $this->getPartlyMockedContentService();

        $expectedContent = $this->mockBuildContentDomainObject($spiContent, $languages);
        $actualContent = $contentService->internalLoadContentById($id, $languages, $versionNo, $useAlwaysAvailable);

        self::assertSame($expectedContent, $actualContent);
    }

    /**
     * @dataProvider internalLoadContentProviderByRemoteId
     */
    public function testInternalLoadContentByRemoteId(string $remoteId, ?array $languages, ?int $versionNo, bool $useAlwaysAvailable)
    {
        $realId = 123;

        $spiContentInfo = new SPIContentInfo([
            'currentVersionNo' => $versionNo ?: 7,
            'id' => $realId,
        ]);

        $spiContent = new SPIContent([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo(['id' => 42, 'contentTypeId' => 123]),
            ]),
        ]);

        $contentService = $this->getPartlyMockedContentService();
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $contentHandler
            ->expects(self::once())
            ->method('loadContentInfoByRemoteId')
            ->with($remoteId)
            ->will(self::returnValue($spiContentInfo));

        $contentHandler
            ->expects(self::once())
            ->method('load')
            ->with($realId, $versionNo, $languages)
            ->willReturn($spiContent);

        $expectedContent = $this->mockBuildContentDomainObject($spiContent, $languages);

        $actualContent = $contentService->internalLoadContentByRemoteId(
            $remoteId,
            $languages,
            $versionNo,
            $useAlwaysAvailable
        );

        self::assertSame($expectedContent, $actualContent);
    }

    public function internalLoadContentProviderById(): array
    {
        return [
            [123, null, null, false],
            [123, null, 456, false],
            [456, null, 123, true],
            [456, null, 2, false],
            [456, ['eng-GB'], 2, true],
            [456, ['eng-GB', 'fre-FR'], null, false],
            [456, ['eng-GB', 'fre-FR', 'nor-NO'], 2, false],
        ];
    }

    public function internalLoadContentProviderByRemoteId(): array
    {
        return [
            ['123', null, null, false],
            ['someRemoteId', null, 456, false],
            ['456', null, 123, false],
            ['someRemoteId', null, 2, false],
            ['someRemoteId', ['eng-GB'], 2, false],
            ['456', ['eng-GB', 'fre-FR'], null, false],
            ['someRemoteId', ['eng-GB', 'fre-FR', 'nor-NO'], 2, false],
        ];
    }

    public function testInternalLoadContentByIdNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $id = 123;
        $versionNo = 7;
        $languages = null;

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $contentHandler
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with($id)
            ->willReturn(new SPIContent\ContentInfo(['id' => $id]));

        $contentHandler
            ->expects(self::once())
            ->method('load')
            ->with($id, $versionNo, $languages)
            ->will(
                self::throwException(
                    $this->createMock(APINotFoundException::class)
                )
            );

        $contentService = $this->getPartlyMockedContentService();
        $contentService->internalLoadContentById($id, $languages, $versionNo);
    }

    public function testInternalLoadContentByRemoteIdNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $remoteId = 'dca290623518d393126d3408b45af6ee';
        $id = 123;
        $versionNo = 7;
        $languages = null;

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $contentHandler
            ->expects(self::once())
            ->method('loadContentInfoByRemoteId')
            ->with($remoteId)
            ->willReturn(new SPIContent\ContentInfo(['id' => $id]));

        $contentHandler
            ->expects(self::once())
            ->method('load')
            ->with($id, $versionNo, $languages)
            ->willThrowException(
                $this->createMock(APINotFoundException::class)
            );

        $contentService = $this->getPartlyMockedContentService();
        $contentService->internalLoadContentByRemoteId($remoteId, $languages, $versionNo);
    }

    /**
     * Test for the loadContentByContentInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByContentInfo
     */
    public function testLoadContentByContentInfo()
    {
        $versionInfo = $this->createMock(APIVersionInfo::class);
        $content = $this->createMock(APIContent::class);
        $content->method('getVersionInfo')
            ->will(self::returnValue($versionInfo));

        $permissionResolver = $this->getPermissionResolverMock();
        $permissionResolver->expects(self::any())
            ->method('canUser')
            ->will(self::returnValue(true));

        $contentServiceMock = $this->getPartlyMockedContentService(
            ['internalLoadContentById']
        );

        $contentServiceMock
            ->method(
                'internalLoadContentById'
            )->with(
                self::equalTo(42),
                self::equalTo(['cro-HR']),
                self::equalTo(7),
                self::equalTo(false)
            )->will(
                self::returnValue($content)
            );

        $result = $contentServiceMock->loadContentByContentInfo(
            new ContentInfo(['id' => 42]),
            ['cro-HR'],
            7
        );

        self::assertEquals($content, $result);
    }

    /**
     * Test for the loadContentByVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByVersionInfo
     */
    public function testLoadContentByVersionInfo()
    {
        $expectedResult = $this->createMock(Content::class);

        $contentServiceMock = $this->getPartlyMockedContentService(
            ['loadContent']
        );
        $contentServiceMock->expects(
            self::once()
        )->method(
            'loadContent'
        )->with(
            self::equalTo(42),
            self::equalTo(['cro-HR']),
            self::equalTo(7),
            self::equalTo(false)
        )->will(
            self::returnValue($expectedResult)
        );

        $result = $contentServiceMock->loadContentByVersionInfo(
            new VersionInfo(
                [
                    'contentInfo' => new ContentInfo(['id' => 42]),
                    'versionNo' => 7,
                ]
            ),
            ['cro-HR']
        );

        self::assertEquals($expectedResult, $result);
    }

    /**
     * Test for the deleteContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteContent
     */
    public function testDeleteContentThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $permissionResolver = $this->getPermissionResolverMock();
        $contentService = $this->getPartlyMockedContentService(['internalLoadContentInfoById']);
        $contentInfo = $this->createMock(APIContentInfo::class);

        $contentInfo->expects(self::any())
            ->method('__get')
            ->willReturnMap(
                [
                    ['id', 42],
                    ['currentVersionNo', 7],
                ]
            );

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();

        $contentHandler
            ->expects(self::once())
            ->method('loadVersionInfo')
            ->with(
                self::equalTo(42),
                self::equalTo(7)
            )->will(
                self::returnValue(new SPIVersionInfo())
            );

        $contentService->expects(self::once())
            ->method('internalLoadContentInfoById')
            ->with(42)
            ->will(self::returnValue($contentInfo));

        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with('content', 'remove')
            ->will(self::returnValue(false));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo */
        $contentService->deleteContent($contentInfo);
    }

    /**
     * Test for the deleteContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteContent
     */
    public function testDeleteContent()
    {
        $repository = $this->getRepositoryMock();
        $permissionResolver = $this->getPermissionResolverMock();

        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with('content', 'remove')
            ->will(self::returnValue(true));

        $contentService = $this->getPartlyMockedContentService(['internalLoadContentInfoById']);
        /** @var \PHPUnit\Framework\MockObject\MockObject $urlAliasHandler */
        $urlAliasHandler = $this->getPersistenceMock()->urlAliasHandler();
        /** @var \PHPUnit\Framework\MockObject\MockObject $locationHandler */
        $locationHandler = $this->getPersistenceMock()->locationHandler();
        $contentInfo = $this->createMock(APIContentInfo::class);

        $contentService->expects(self::once())
            ->method('internalLoadContentInfoById')
            ->with(42)
            ->will(self::returnValue($contentInfo));

        $contentInfo->expects(self::any())
            ->method('__get')
            ->willReturnMap(
                [
                    ['id', 42],
                    ['currentVersionNo', 7],
                ]
            );

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();

        $contentHandler
            ->expects(self::once())
            ->method('loadVersionInfo')
            ->with(
                self::equalTo(42),
                self::equalTo(7)
            )->will(
                self::returnValue(new SPIVersionInfo())
            );

        $repository->expects(self::once())->method('beginTransaction');

        $spiLocations = [
            new SPILocation(['id' => 1]),
            new SPILocation(['id' => 2]),
        ];
        $locationHandler->expects(self::once())
            ->method('loadLocationsByContent')
            ->with(42)
            ->will(self::returnValue($spiLocations));

        $contentHandler->expects(self::once())
            ->method('deleteContent')
            ->with(42);

        foreach ($spiLocations as $index => $spiLocation) {
            $urlAliasHandler->expects(self::at($index))
                ->method('locationDeleted')
                ->with($spiLocation->id);
        }

        $repository->expects(self::once())->method('commit');

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo */
        $contentService->deleteContent($contentInfo);
    }

    /**
     * Test for the deleteContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteContent
     */
    public function testDeleteContentWithRollback()
    {
        $this->expectException(\Exception::class);

        $repository = $this->getRepositoryMock();
        $permissionResolver = $this->getPermissionResolverMock();

        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with('content', 'remove')
            ->will(self::returnValue(true));

        $contentService = $this->getPartlyMockedContentService(['internalLoadContentInfoById']);
        /** @var \PHPUnit\Framework\MockObject\MockObject $locationHandler */
        $locationHandler = $this->getPersistenceMock()->locationHandler();

        $contentInfo = $this->createMock(APIContentInfo::class);

        $contentService->expects(self::once())
            ->method('internalLoadContentInfoById')
            ->with(42)
            ->will(self::returnValue($contentInfo));

        $contentInfo->expects(self::any())
            ->method('__get')
            ->willReturnMap(
                [
                    ['id', 42],
                    ['currentVersionNo', 7],
                ]
            );

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();

        $contentHandler
            ->expects(self::once())
            ->method('loadVersionInfo')
            ->with(
                self::equalTo(42),
                self::equalTo(7)
            )->will(
                self::returnValue(new SPIVersionInfo())
            );

        $repository->expects(self::once())->method('beginTransaction');

        $locationHandler->expects(self::once())
            ->method('loadLocationsByContent')
            ->with(42)
            ->will(self::throwException(new \Exception()));

        $repository->expects(self::once())->method('rollback');

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo */
        $contentService->deleteContent($contentInfo);
    }

    /**
     * Test for the deleteVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteVersion
     */
    public function testDeleteVersionThrowsBadStateExceptionLastVersion()
    {
        $this->expectException(BadStateException::class);

        $repository = $this->getRepositoryMock();
        $permissionResolver = $this->getPermissionResolverMock();

        $permissionResolver
            ->expects(self::once())
            ->method('canUser')
            ->with('content', 'versionremove')
            ->will(self::returnValue(true));
        $repository
            ->expects(self::never())
            ->method('beginTransaction');

        $contentService = $this->getPartlyMockedContentService();
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandler */
        $contentHandler = $this->getPersistenceMock()->contentHandler();
        $contentInfo = $this->createMock(APIContentInfo::class);
        $versionInfo = $this->createMock(APIVersionInfo::class);

        $contentInfo
            ->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(42));

        $versionInfo
            ->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['versionNo', 123],
                        ['contentInfo', $contentInfo],
                    ]
                )
            );
        $versionInfo
            ->expects(self::once())
            ->method('isPublished')
            ->willReturn(false);

        $contentHandler
            ->expects(self::once())
            ->method('listVersions')
            ->with(42)
            ->will(self::returnValue(['version']));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo */
        $contentService->deleteVersion($versionInfo);
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     */
    public function testCreateContentThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repositoryMock = $this->getRepositoryMock();

        $permissionResolver = $this->getPermissionResolverMock();
        $permissionResolver->expects(self::once())
            ->method('getCurrentUserReference')
            ->will(self::returnValue(new UserReference(169)));

        $mockedService = $this->getPartlyMockedContentService();
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $contentType = new ContentType(
            [
                'id' => 123,
                'fieldDefinitions' => [],
            ]
        );
        $contentCreateStruct = new ContentCreateStruct(
            [
                'ownerId' => 169,
                'alwaysAvailable' => false,
                'mainLanguageCode' => 'eng-US',
                'contentType' => $contentType,
            ]
        );

        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(self::equalTo(123))
            ->will(self::returnValue($contentType));

        $repositoryMock->expects(self::once())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));

        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('create'),
                self::isInstanceOf(get_class($contentCreateStruct)),
                self::equalTo([])
            )->will(self::returnValue(false));

        $mockedService->createContent(
            new ContentCreateStruct(
                [
                    'mainLanguageCode' => 'eng-US',
                    'contentType' => $contentType,
                ]
            ),
            []
        );
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @exceptionMessage Argument '$contentCreateStruct' is invalid: Another content with remoteId 'faraday' exists
     */
    public function testCreateContentThrowsInvalidArgumentExceptionDuplicateRemoteId()
    {
        $this->expectException(InvalidArgumentException::class);

        $repositoryMock = $this->getRepositoryMock();
        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->willReturn($this->createMock(UserReference::class));

        $mockedService = $this->getPartlyMockedContentService(['loadContentByRemoteId']);
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $contentType = new ContentType(
            [
                'id' => 123,
                'fieldDefinitions' => [],
            ]
        );
        $contentCreateStruct = new ContentCreateStruct(
            [
                'ownerId' => 169,
                'alwaysAvailable' => false,
                'remoteId' => 'faraday',
                'mainLanguageCode' => 'eng-US',
                'contentType' => $contentType,
            ]
        );

        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(self::equalTo(123))
            ->will(self::returnValue($contentType));

        $repositoryMock->expects(self::once())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));

        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('create'),
                self::isInstanceOf(get_class($contentCreateStruct)),
                self::equalTo([])
            )->will(self::returnValue(true));

        $mockedService->expects(self::once())
            ->method('loadContentByRemoteId')
            ->with($contentCreateStruct->remoteId)
            ->will(self::returnValue($this->createMock(Content::class)));

        $mockedService->createContent(
            new ContentCreateStruct(
                [
                    'remoteId' => 'faraday',
                    'mainLanguageCode' => 'eng-US',
                    'contentType' => $contentType,
                ]
            ),
            []
        );
    }

    /**
     * @param string $mainLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition[] $fieldDefinitions
     *
     * @return array
     */
    protected function mapStructFieldsForCreate($mainLanguageCode, $structFields, $fieldDefinitions)
    {
        $mappedFieldDefinitions = [];
        foreach ($fieldDefinitions as $fieldDefinition) {
            $mappedFieldDefinitions[$fieldDefinition->identifier] = $fieldDefinition;
        }

        $mappedStructFields = [];
        foreach ($structFields as $structField) {
            if ($structField->languageCode === null) {
                $languageCode = $mainLanguageCode;
            } else {
                $languageCode = $structField->languageCode;
            }

            $mappedStructFields[$structField->fieldDefIdentifier][$languageCode] = (string)$structField->value;
        }

        return $mappedStructFields;
    }

    /**
     * Returns full, possibly redundant array of field values, indexed by field definition
     * identifier and language code.
     *
     * @param string $mainLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition[] $fieldDefinitions
     * @param array $languageCodes
     *
     * @return array
     *
     * @throws \RuntimeException Method is intended to be used only with consistent fixtures
     */
    protected function determineValuesForCreate(
        $mainLanguageCode,
        array $structFields,
        array $fieldDefinitions,
        array $languageCodes
    ) {
        $mappedStructFields = $this->mapStructFieldsForCreate(
            $mainLanguageCode,
            $structFields,
            $fieldDefinitions
        );

        $values = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $identifier = $fieldDefinition->identifier;
            foreach ($languageCodes as $languageCode) {
                if (!$fieldDefinition->isTranslatable) {
                    if (isset($mappedStructFields[$identifier][$mainLanguageCode])) {
                        $values[$identifier][$languageCode] = $mappedStructFields[$identifier][$mainLanguageCode];
                    } else {
                        $values[$identifier][$languageCode] = (string)$fieldDefinition->defaultValue;
                    }
                    continue;
                }

                if (isset($mappedStructFields[$identifier][$languageCode])) {
                    $values[$identifier][$languageCode] = $mappedStructFields[$identifier][$languageCode];
                    continue;
                }

                $values[$identifier][$languageCode] = (string)$fieldDefinition->defaultValue;
            }
        }

        return $this->stubValues($values);
    }

    /**
     * @param string $mainLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     *
     * @return string[]
     */
    protected function determineLanguageCodesForCreate($mainLanguageCode, array $structFields)
    {
        $languageCodes = [];

        foreach ($structFields as $field) {
            if ($field->languageCode === null || isset($languageCodes[$field->languageCode])) {
                continue;
            }

            $languageCodes[$field->languageCode] = true;
        }

        $languageCodes[$mainLanguageCode] = true;

        return array_keys($languageCodes);
    }

    /**
     * Asserts that calling createContent() with given API field set causes calling
     * Handler::createContent() with given SPI field set.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field[] $spiFields
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition[] $fieldDefinitions
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct[] $locationCreateStructs
     */
    protected function assertForTestCreateContentNonRedundantFieldSet(
        string $mainLanguageCode,
        array $structFields,
        array $spiFields,
        array $fieldDefinitions,
        array $locationCreateStructs = [],
        bool $withObjectStates = false,
        bool $execute = true
    ): ContentCreateStruct {
        $repositoryMock = $this->getRepositoryMock();
        $mockedService = $this->getPartlyMockedContentService();
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandlerMock */
        $contentHandlerMock = $this->getPersistenceMock()->contentHandler();
        /** @var \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock */
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        /** @var \PHPUnit\Framework\MockObject\MockObject $objectStateHandlerMock */
        $objectStateHandlerMock = $this->getPersistenceMock()->objectStateHandler();
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $relationProcessorMock = $this->getRelationProcessorMock();
        $nameSchemaServiceMock = $this->getNameSchemaServiceMock();
        $permissionResolverMock = $this->getPermissionResolverMock();
        $fieldTypeMock = $this->createMock(SPIFieldType::class);
        $languageCodes = $this->determineLanguageCodesForCreate($mainLanguageCode, $structFields);

        list($contentType, $contentCreateStruct) = $this->provideCommonCreateContentObjects(
            $fieldDefinitions,
            $structFields,
            $mainLanguageCode
        );

        $this->commonContentCreateMocks(
            $languageHandlerMock,
            $contentTypeServiceMock,
            $repositoryMock,
            $contentType
        );

        $repositoryMock->expects(self::once())->method('beginTransaction');

        $that = $this;
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('create'),
                self::isInstanceOf(APIContentCreateStruct::class),
                self::equalTo($locationCreateStructs)
            )->will(
                self::returnCallback(
                    static function () use ($that, $contentCreateStruct): bool {
                        $that->assertEquals($contentCreateStruct, func_get_arg(2));

                        return true;
                    }
                )
            );

        $this->getUniqueHashDomainMapperMock($domainMapperMock, $that, $contentCreateStruct);
        $this->acceptFieldTypeValueMock($fieldTypeMock);
        $this->toHashFieldTypeMock($fieldTypeMock);

        $fieldTypeMock->expects(self::any())
            ->method('toPersistenceValue')
            ->will(
                self::returnCallback(
                    static function (ValueStub $value): string {
                        return (string)$value;
                    }
                )
            );

        $this->isEmptyValueFieldTypeMock($fieldTypeMock);

        $fieldTypeMock->expects(self::any())
            ->method('validate')
            ->will(self::returnValue([]));

        $this->getFieldTypeRegistryMock()->expects(self::any())
            ->method('getFieldType')
            ->will(self::returnValue($fieldTypeMock));

        $relationProcessorMock
            ->expects(self::exactly(count($fieldDefinitions) * count($languageCodes)))
            ->method('appendFieldRelations')
            ->with(
                self::isType('array'),
                self::isType('array'),
                self::isInstanceOf(SPIFieldType::class),
                self::isInstanceOf(Value::class),
                self::anything()
            );

        $values = $this->determineValuesForCreate(
            $mainLanguageCode,
            $structFields,
            $fieldDefinitions,
            $languageCodes
        );
        $nameSchemaServiceMock->expects(self::once())
            ->method('resolveNameSchema')
            ->with(
                self::equalTo($contentType->nameSchema),
                self::equalTo($contentType),
                self::equalTo($values),
                self::equalTo($languageCodes)
            )->will(self::returnValue([]));

        $relationProcessorMock->expects(self::any())
            ->method('processFieldRelations')
            ->with(
                self::isType('array'),
                self::equalTo(42),
                self::isType('int'),
                self::equalTo($contentType),
                self::equalTo([])
            );

        if (!$withObjectStates) {
            $objectStateHandlerMock->expects(self::once())
                ->method('loadAllGroups')
                ->will(self::returnValue([]));
        }

        if ($execute) {
            $spiContentCreateStruct = new SPIContentCreateStruct(
                [
                    'name' => [],
                    'typeId' => 123,
                    'sectionId' => 1,
                    'ownerId' => 169,
                    'remoteId' => 'hash',
                    'fields' => $spiFields,
                    'modified' => time(),
                    'initialLanguageId' => 4242,
                ]
            );
            $spiContentCreateStruct2 = clone $spiContentCreateStruct;
            ++$spiContentCreateStruct2->modified;

            $spiContent = new SPIContent(
                [
                    'versionInfo' => new SPIContent\VersionInfo(
                        [
                            'contentInfo' => new SPIContent\ContentInfo(['id' => 42]),
                            'versionNo' => 7,
                        ]
                    ),
                ]
            );

            $contentHandlerMock->expects(self::once())
                ->method('create')
                ->with(self::logicalOr($spiContentCreateStruct, $spiContentCreateStruct2))
                ->will(self::returnValue($spiContent));

            $repositoryMock->expects(self::once())->method('commit');
            $domainMapperMock->expects(self::once())
                ->method('buildContentDomainObject')
                ->with(
                    self::isInstanceOf(SPIContent::class),
                    self::equalTo($contentType)
                )
                ->willReturn(self::createMock(APIContent::class));

            $mockedService->createContent($contentCreateStruct, []);
        }

        return $contentCreateStruct;
    }

    public function providerForTestCreateContentNonRedundantFieldSet1()
    {
        $spiFields = [
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue'),
                    'languageCode' => 'eng-US',
                ]
            ),
        ];

        return [
            // 0. Without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                ],
                $spiFields,
            ],
            // 1. Without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue'),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields,
            ],
        ];
    }

    /**
     * Test for the createContent() method.
     *
     * Testing the simplest use case.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::cloneField
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getDefaultObjectStates
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @dataProvider providerForTestCreateContentNonRedundantFieldSet1
     */
    public function testCreateContentNonRedundantFieldSet1($mainLanguageCode, $structFields, $spiFields)
    {
        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('someValue'),
                ]
            ),
        ];

        $this->assertForTestCreateContentNonRedundantFieldSet(
            $mainLanguageCode,
            $structFields,
            $spiFields,
            $fieldDefinitions
        );
    }

    public function providerForTestCreateContentNonRedundantFieldSet2()
    {
        $spiFields = [
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1'),
                    'languageCode' => 'eng-US',
                ]
            ),
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue2'),
                    'languageCode' => 'ger-DE',
                ]
            ),
        ];

        return [
            // 0. With language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'ger-DE',
                        ]
                    ),
                ],
                $spiFields,
            ],
            // 1. Without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'ger-DE',
                        ]
                    ),
                ],
                $spiFields,
            ],
        ];
    }

    /**
     * Test for the createContent() method.
     *
     * Testing multiple languages with multiple translatable fields with empty default value.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::cloneField
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getDefaultObjectStates
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @dataProvider providerForTestCreateContentNonRedundantFieldSet2
     */
    public function testCreateContentNonRedundantFieldSet2($mainLanguageCode, $structFields, $spiFields)
    {
        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier1',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier2',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
        ];

        $this->assertForTestCreateContentNonRedundantFieldSet(
            $mainLanguageCode,
            $structFields,
            $spiFields,
            $fieldDefinitions
        );
    }

    public function providerForTestCreateContentNonRedundantFieldSetComplex()
    {
        $spiFields0 = [
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('defaultValue2'),
                    'languageCode' => 'eng-US',
                ]
            ),
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_D,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('defaultValue4'),
                    'languageCode' => 'eng-US',
                ]
            ),
        ];
        $spiFields1 = [
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1'),
                    'languageCode' => 'ger-DE',
                ]
            ),
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('defaultValue2'),
                    'languageCode' => 'ger-DE',
                ]
            ),
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue2'),
                    'languageCode' => 'eng-US',
                ]
            ),
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_D,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue4'),
                    'languageCode' => 'eng-US',
                ]
            ),
        ];

        return [
            // 0. Creating by default values only
            [
                'eng-US',
                [],
                $spiFields0,
            ],
            // 1. Multiple languages with language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'ger-DE',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier4',
                            'value' => new ValueStub('newValue4'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                ],
                $spiFields1,
            ],
            // 2. Multiple languages without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'ger-DE',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier4',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue4'),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields1,
            ],
        ];
    }

    protected function fixturesForTestCreateContentNonRedundantFieldSetComplex()
    {
        return [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier1',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier2',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue2'),
                ]
            ),
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_C,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier3',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_D,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier4',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue4'),
                ]
            ),
        ];
    }

    /**
     * Test for the createContent() method.
     *
     * Testing multiple languages with multiple translatable fields with empty default value.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::cloneField
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getDefaultObjectStates
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @dataProvider providerForTestCreateContentNonRedundantFieldSetComplex
     */
    public function testCreateContentNonRedundantFieldSetComplex($mainLanguageCode, $structFields, $spiFields)
    {
        $fieldDefinitions = $this->fixturesForTestCreateContentNonRedundantFieldSetComplex();

        $this->assertForTestCreateContentNonRedundantFieldSet(
            $mainLanguageCode,
            $structFields,
            $spiFields,
            $fieldDefinitions
        );
    }

    public function providerForTestCreateContentWithInvalidLanguage()
    {
        return [
            [
                'eng-GB',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'Klingon',
                        ]
                    ),
                ],
            ],
            [
                'Klingon',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @dataProvider providerForTestCreateContentWithInvalidLanguage
     */
    public function testCreateContentWithInvalidLanguage($mainLanguageCode, $structFields)
    {
        $this->expectException(APINotFoundException::class);
        $this->expectExceptionMessage('Could not find \'Language\' with identifier \'Klingon\'');

        $repositoryMock = $this->getRepositoryMock();
        $mockedService = $this->getPartlyMockedContentService();
        /** @var \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock */
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $permissionResolver = $this->getPermissionResolverMock();

        $fieldTypeMock = $this->createMock(FieldType::class);
        $this->acceptFieldTypeValueMock($fieldTypeMock);
        $this->toHashFieldTypeMock($fieldTypeMock);
        $this->getFieldTypeFieldTypeRegistryMock($fieldTypeMock);

        $contentType = new ContentType(
            [
                'id' => 123,
                'fieldDefinitions' => new FieldDefinitionCollection([
                    new FieldDefinition([
                        'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                        'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                        'isTranslatable' => false,
                        'identifier' => 'identifier',
                        'isRequired' => false,
                        'defaultValue' => new ValueStub('someValue'),
                    ]),
                ]),
            ]
        );
        $contentCreateStruct = new ContentCreateStruct(
            [
                'fields' => $structFields,
                'mainLanguageCode' => $mainLanguageCode,
                'contentType' => $contentType,
                'alwaysAvailable' => false,
                'ownerId' => 169,
                'sectionId' => 1,
            ]
        );

        $languageHandlerMock->expects(self::any())
            ->method('loadByLanguageCode')
            ->with(self::isType('string'))
            ->will(
                self::returnCallback(
                    static function ($languageCode) {
                        if ($languageCode === 'Klingon') {
                            throw new NotFoundException('Language', 'Klingon');
                        }

                        return new Language(['id' => 4242]);
                    }
                )
            );

        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(self::equalTo($contentType->id))
            ->will(self::returnValue($contentType));

        $repositoryMock->expects(self::once())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));

        $that = $this;
        $permissionResolver->expects(self::any())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('create'),
                self::isInstanceOf(APIContentCreateStruct::class),
                self::equalTo([])
            )->will(
                self::returnCallback(
                    static function () use ($that, $contentCreateStruct): bool {
                        $that->assertEquals($contentCreateStruct, func_get_arg(2));

                        return true;
                    }
                )
            );

        $domainMapperMock->expects(self::once())
            ->method('getUniqueHash')
            ->with(self::isInstanceOf(APIContentCreateStruct::class))
            ->will(
                self::returnCallback(
                    static function ($object) use ($that, $contentCreateStruct): string {
                        $that->assertEquals($contentCreateStruct, $object);

                        return 'hash';
                    }
                )
            );

        $mockedService->createContent($contentCreateStruct, []);
    }

    protected function assertForCreateContentContentValidationException(
        $mainLanguageCode,
        $structFields,
        $fieldDefinitions = []
    ) {
        $repositoryMock = $this->getRepositoryMock();
        $mockedService = $this->getPartlyMockedContentService(['loadContentByRemoteId']);
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $permissionResolver = $this->getPermissionResolverMock();

        $fieldTypeMock = $this->createMock(FieldType::class);
        $fieldTypeMock->expects(self::any())
            ->method('acceptValue')
            ->will(
                self::returnCallback(
                    static function ($valueString) {
                        return new ValueStub($valueString);
                    }
                )
            );

        $this->toHashFieldTypeMock($fieldTypeMock);
        $this->getFieldTypeFieldTypeRegistryMock($fieldTypeMock);

        $contentType = new ContentType(
            [
                'id' => 123,
                'fieldDefinitions' => new FieldDefinitionCollection($fieldDefinitions),
            ]
        );
        $contentCreateStruct = new ContentCreateStruct(
            [
                'ownerId' => 169,
                'alwaysAvailable' => false,
                'remoteId' => 'faraday',
                'mainLanguageCode' => $mainLanguageCode,
                'fields' => $structFields,
                'contentType' => $contentType,
            ]
        );

        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(self::equalTo(123))
            ->will(self::returnValue($contentType));

        $repositoryMock->expects(self::once())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));

        $permissionResolver->expects(self::any())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('create'),
                self::isInstanceOf(get_class($contentCreateStruct)),
                self::equalTo([])
            )->will(self::returnValue(true));

        $mockedService->expects(self::any())
            ->method('loadContentByRemoteId')
            ->with($contentCreateStruct->remoteId)
            ->will(
                self::throwException(new NotFoundException('Content', 'faraday'))
            );

        $mockedService->createContent($contentCreateStruct, []);
    }

    public function providerForTestCreateContentThrowsContentValidationExceptionFieldDefinition()
    {
        return [
            [
                'eng-GB',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @dataProvider providerForTestCreateContentThrowsContentValidationExceptionFieldDefinition
     */
    public function testCreateContentThrowsContentValidationExceptionFieldDefinition($mainLanguageCode, $structFields)
    {
        $this->expectException(ContentValidationException::class);
        $this->expectExceptionMessage('Field definition \'identifier\' does not exist in the given content type');

        $this->assertForCreateContentContentValidationException(
            $mainLanguageCode,
            $structFields,
            []
        );
    }

    public function providerForTestCreateContentThrowsContentValidationExceptionTranslation()
    {
        return [
            [
                'eng-GB',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @dataProvider providerForTestCreateContentThrowsContentValidationExceptionTranslation
     */
    public function testCreateContentThrowsContentValidationExceptionTranslation($mainLanguageCode, $structFields)
    {
        $this->expectException(ContentValidationException::class);
        $this->expectExceptionMessage('You cannot set a value for the non-translatable Field definition \'identifier\' in language \'eng-US\'');

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
        ];

        $this->assertForCreateContentContentValidationException(
            $mainLanguageCode,
            $structFields,
            $fieldDefinitions
        );
    }

    private function provideCommonCreateContentObjects(array $fieldDefinitions, array $structFields, $mainLanguageCode): array
    {
        $contentType = new ContentType(
            [
                'id' => 123,
                'fieldDefinitions' => new FieldDefinitionCollection($fieldDefinitions),
                'nameSchema' => '<nameSchema>',
            ]
        );
        $contentCreateStruct = new ContentCreateStruct(
            [
                'fields' => $structFields,
                'mainLanguageCode' => $mainLanguageCode,
                'contentType' => $contentType,
                'alwaysAvailable' => false,
                'ownerId' => 169,
                'sectionId' => 1,
            ]
        );

        return [$contentType, $contentCreateStruct];
    }

    private function commonContentCreateMocks(
        \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock,
        \PHPUnit\Framework\MockObject\MockObject $contentTypeServiceMock,
        \PHPUnit\Framework\MockObject\MockObject $repositoryMock,
        ContentType $contentType
    ): void {
        $this->loadByLanguageCodeMock($languageHandlerMock);

        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(self::equalTo($contentType->id))
            ->will(self::returnValue($contentType));

        $repositoryMock->expects(self::once())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));
    }

    private function loadByLanguageCodeMock(\PHPUnit\Framework\MockObject\MockObject $languageHandlerMock): void
    {
        $languageHandlerMock->expects(self::any())
            ->method('loadByLanguageCode')
            ->with(self::isType('string'))
            ->will(
                self::returnCallback(
                    static function () {
                        return new Language(['id' => 4242]);
                    }
                )
            );
    }

    /**
     * Asserts behaviour necessary for testing ContentFieldValidationException because of required
     * field being empty.
     *
     * @param string $mainLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition[] $fieldDefinitions
     *
     * @return mixed
     */
    protected function assertForTestCreateContentRequiredField(
        $mainLanguageCode,
        array $structFields,
        array $fieldDefinitions
    ) {
        $repositoryMock = $this->getRepositoryMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock */
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $fieldTypeMock = $this->createMock(SPIFieldType::class);
        $permissionResolver = $this->getPermissionResolverMock();

        list($contentType, $contentCreateStruct) = $this->provideCommonCreateContentObjects(
            $fieldDefinitions,
            $structFields,
            $mainLanguageCode
        );

        $this->commonContentCreateMocks(
            $languageHandlerMock,
            $contentTypeServiceMock,
            $repositoryMock,
            $contentType
        );

        $that = $this;
        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('create'),
                self::isInstanceOf(APIContentCreateStruct::class),
                self::equalTo([])
            )->will(
                self::returnCallback(
                    static function () use ($that, $contentCreateStruct): bool {
                        $that->assertEquals($contentCreateStruct, func_get_arg(2));

                        return true;
                    }
                )
            );

        $this->getUniqueHashDomainMapperMock($domainMapperMock, $that, $contentCreateStruct);

        $this->acceptFieldTypeValueMock($fieldTypeMock);
        $this->toHashFieldTypeMock($fieldTypeMock);

        $this->isEmptyValueFieldTypeMock($fieldTypeMock);

        $fieldTypeMock->expects(self::any())
            ->method('validate')
            ->will(self::returnValue([]));

        $this->getFieldTypeRegistryMock()->expects(self::any())
            ->method('getFieldType')
            ->will(self::returnValue($fieldTypeMock));

        return $contentCreateStruct;
    }

    public function providerForTestCreateContentThrowsContentValidationExceptionRequiredField()
    {
        return [
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => null,
                        ]
                    ),
                ],
                'identifier',
                'eng-US',
            ],
        ];
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @dataProvider providerForTestCreateContentThrowsContentValidationExceptionRequiredField
     */
    public function testCreateContentRequiredField(
        $mainLanguageCode,
        $structFields,
        $identifier,
        $languageCode
    ) {
        $this->expectException(ContentFieldValidationException::class);

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier',
                    'isRequired' => true,
                    'defaultValue' => new ValueStub('defaultValue'),
                ]
            ),
        ];
        $contentCreateStruct = $this->assertForTestCreateContentRequiredField(
            $mainLanguageCode,
            $structFields,
            $fieldDefinitions
        );

        $mockedService = $this->getPartlyMockedContentService();

        try {
            $mockedService->createContent($contentCreateStruct, []);
        } catch (ContentValidationException $e) {
            self::assertEquals(
                "Value for required field definition '{$identifier}' with language '{$languageCode}' is empty",
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Asserts behaviour necessary for testing ContentFieldValidationException because of
     * field not being valid.
     *
     * @param string $mainLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition[] $fieldDefinitions
     */
    protected function assertForTestCreateContentThrowsContentFieldValidationException(
        $mainLanguageCode,
        array $structFields,
        array $fieldDefinitions
    ): array {
        $repositoryMock = $this->getRepositoryMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock */
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $relationProcessorMock = $this->getRelationProcessorMock();
        $fieldTypeMock = $this->createMock(SPIFieldType::class);
        $languageCodes = $this->determineLanguageCodesForCreate($mainLanguageCode, $structFields);
        $permissionResolver = $this->getPermissionResolverMock();

        $contentType = new ContentType(
            [
                'id' => 123,
                'fieldDefinitions' => new FieldDefinitionCollection($fieldDefinitions),
                'nameSchema' => '<nameSchema>',
            ]
        );
        $contentCreateStruct = new ContentCreateStruct(
            [
                'fields' => $structFields,
                'mainLanguageCode' => $mainLanguageCode,
                'contentType' => $contentType,
                'alwaysAvailable' => false,
                'ownerId' => 169,
                'sectionId' => 1,
            ]
        );

        $this->commonContentCreateMocks(
            $languageHandlerMock,
            $contentTypeServiceMock,
            $repositoryMock,
            $contentType
        );

        $that = $this;
        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('create'),
                self::isInstanceOf(APIContentCreateStruct::class),
                self::equalTo([])
            )->will(
                self::returnCallback(
                    static function () use ($that, $contentCreateStruct): bool {
                        $that->assertEquals($contentCreateStruct, func_get_arg(2));

                        return true;
                    }
                )
            );

        $this->getUniqueHashDomainMapperMock($domainMapperMock, $that, $contentCreateStruct);

        $this->getFieldTypeRegistryMock()->expects(self::any())
            ->method('getFieldType')
            ->will(self::returnValue($fieldTypeMock));

        $relationProcessorMock
            ->expects(self::any())
            ->method('appendFieldRelations')
            ->with(
                self::isType('array'),
                self::isType('array'),
                self::isInstanceOf(SPIFieldType::class),
                self::isInstanceOf(Value::class),
                self::anything()
            );

        $fieldValues = $this->determineValuesForCreate(
            $mainLanguageCode,
            $structFields,
            $fieldDefinitions,
            $languageCodes
        );
        $allFieldErrors = [];
        $emptyValue = new ValueStub(self::EMPTY_FIELD_VALUE);

        $fieldTypeMock
            ->method('acceptValue')
            ->will(
                self::returnCallback(
                    static function ($value) {
                        return $value instanceof SPIValue
                            ? $value
                            : new ValueStub($value);
                    }
                )
            );

        $fieldTypeMock
            ->method('isEmptyValue')
            ->will(
                self::returnCallback(
                    static function (ValueStub $value) use ($emptyValue): bool {
                        return (string)$emptyValue === (string)$value;
                    }
                )
            );

        $this->toHashFieldTypeMock($fieldTypeMock);

        $emptyValue = new ValueStub(self::EMPTY_FIELD_VALUE);
        foreach ($contentType->getFieldDefinitions() as $fieldDefinition) {
            foreach ($fieldValues[$fieldDefinition->identifier] as $languageCode => $value) {
                if ((string)$emptyValue === (string)$value) {
                    continue;
                }

                $fieldTypeMock
                    ->method('validate')
                    ->willReturn(new ValidationError(1));

                $allFieldErrors[$fieldDefinition->id][$languageCode] = new ValidationError(1);
            }
        }

        return [$contentCreateStruct, $allFieldErrors];
    }

    public function providerForTestCreateContentThrowsContentFieldValidationException()
    {
        return $this->providerForTestCreateContentNonRedundantFieldSetComplex();
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @dataProvider providerForTestCreateContentThrowsContentFieldValidationException
     */
    public function testCreateContentThrowsContentFieldValidationException($mainLanguageCode, $structFields): void
    {
        $this->expectException(ContentFieldValidationException::class);
        $this->expectExceptionMessage('Content fields did not validate');

        $fieldDefinitions = $this->fixturesForTestCreateContentNonRedundantFieldSetComplex();
        list($contentCreateStruct, $allFieldErrors) =
            $this->assertForTestCreateContentThrowsContentFieldValidationException(
                $mainLanguageCode,
                $structFields,
                $fieldDefinitions
            );

        $mockedService = $this->getPartlyMockedContentService();

        try {
            $mockedService->createContent($contentCreateStruct);
        } catch (ContentFieldValidationException $e) {
            self::assertEquals($allFieldErrors, $e->getFieldErrors());
            throw $e;
        }
    }

    private function acceptFieldTypeValueMock(\PHPUnit\Framework\MockObject\MockObject $fieldTypeMock): void
    {
        $fieldTypeMock->expects(self::any())
            ->method('acceptValue')
            ->will(
                self::returnCallback(
                    static function ($valueString) {
                        return new ValueStub($valueString);
                    }
                )
            );
    }

    private function toHashFieldTypeMock(\PHPUnit\Framework\MockObject\MockObject $fieldTypeMock): void
    {
        $fieldTypeMock
            ->method('toHash')
            ->willReturnCallback(static function (SPIValue $value) {
                return ['value' => $value->value];
            });
    }

    private function getFieldTypeFieldTypeRegistryMock(\PHPUnit\Framework\MockObject\MockObject $fieldTypeMock): void
    {
        $this->getFieldTypeRegistryMock()->expects(self::any())
            ->method('getFieldType')
            ->will(self::returnValue($fieldTypeMock));
    }

    private function isEmptyValueFieldTypeMock(\PHPUnit\Framework\MockObject\MockObject $fieldTypeMock): void
    {
        $emptyValue = new ValueStub(self::EMPTY_FIELD_VALUE);
        $fieldTypeMock->expects(self::any())
            ->method('isEmptyValue')
            ->will(
                self::returnCallback(
                    static function (ValueStub $value) use ($emptyValue): bool {
                        return (string)$emptyValue === (string)$value;
                    }
                )
            );
    }

    private function getUniqueHashDomainMapperMock(
        \PHPUnit\Framework\MockObject\MockObject $domainMapperMock,
        self $that,
        ContentCreateStruct $contentCreateStruct
    ): void {
        $domainMapperMock->expects(self::once())
            ->method('getUniqueHash')
            ->with(self::isInstanceOf(APIContentCreateStruct::class))
            ->will(
                self::returnCallback(
                    static function ($object) use ($that, $contentCreateStruct): string {
                        $that->assertEquals($contentCreateStruct, $object);

                        return 'hash';
                    }
                )
            );
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::buildSPILocationCreateStructs
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     */
    public function testCreateContentWithLocations()
    {
        $spiFields = [
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('defaultValue'),
                    'languageCode' => 'eng-US',
                ]
            ),
        ];
        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue'),
                ]
            ),
        ];

        // Set up a simple case that will pass
        $locationCreateStruct1 = new LocationCreateStruct(['parentLocationId' => 321]);
        $locationCreateStruct2 = new LocationCreateStruct(['parentLocationId' => 654]);
        $locationCreateStructs = [$locationCreateStruct1, $locationCreateStruct2];
        $contentCreateStruct = $this->assertForTestCreateContentNonRedundantFieldSet(
            'eng-US',
            [],
            $spiFields,
            $fieldDefinitions,
            $locationCreateStructs,
            false,
            // Do not execute
            false
        );

        $repositoryMock = $this->getRepositoryMock();
        $mockedService = $this->getPartlyMockedContentService();
        $locationServiceMock = $this->getLocationServiceMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject $handlerMock */
        $handlerMock = $this->getPersistenceMock()->contentHandler();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $spiLocationCreateStruct = new SPILocation\CreateStruct();
        $parentLocation = new Location(['contentInfo' => new ContentInfo(['sectionId' => 1])]);

        $locationServiceMock->expects(self::at(0))
            ->method('loadLocation')
            ->with(self::equalTo(321))
            ->will(self::returnValue($parentLocation));

        $locationServiceMock->expects(self::at(1))
            ->method('loadLocation')
            ->with(self::equalTo(654))
            ->will(self::returnValue($parentLocation));

        $repositoryMock->expects(self::atLeastOnce())
            ->method('getLocationService')
            ->will(self::returnValue($locationServiceMock));

        $domainMapperMock->expects(self::at(1))
            ->method('buildSPILocationCreateStruct')
            ->with(
                self::equalTo($locationCreateStruct1),
                self::equalTo($parentLocation),
                self::equalTo(true),
                self::equalTo(null),
                self::equalTo(null),
                self::equalTo(false)
            )->will(self::returnValue($spiLocationCreateStruct));

        $domainMapperMock->expects(self::at(2))
            ->method('buildSPILocationCreateStruct')
            ->with(
                self::equalTo($locationCreateStruct2),
                self::equalTo($parentLocation),
                self::equalTo(false),
                self::equalTo(null),
                self::equalTo(null),
                self::equalTo(false)
            )->will(self::returnValue($spiLocationCreateStruct));

        $spiContentCreateStruct = new SPIContentCreateStruct(
            [
                'name' => [],
                'typeId' => 123,
                'sectionId' => 1,
                'ownerId' => 169,
                'remoteId' => 'hash',
                'fields' => $spiFields,
                'modified' => time(),
                'initialLanguageId' => 4242,
                'locations' => [$spiLocationCreateStruct, $spiLocationCreateStruct],
            ]
        );
        $spiContentCreateStruct2 = clone $spiContentCreateStruct;
        ++$spiContentCreateStruct2->modified;

        $spiContent = new SPIContent(
            [
                'versionInfo' => new SPIContent\VersionInfo(
                    [
                        'contentInfo' => new SPIContent\ContentInfo(['id' => 42]),
                        'versionNo' => 7,
                    ]
                ),
            ]
        );

        $handlerMock->expects(self::once())
            ->method('create')
            ->with(self::logicalOr($spiContentCreateStruct, $spiContentCreateStruct2))
            ->will(self::returnValue($spiContent));

        $domainMapperMock->expects(self::once())
            ->method('buildContentDomainObject')
            ->with(
                self::isInstanceOf(SPIContent::class),
                self::isInstanceOf(APIContentType::class)
            )
            ->willReturn($this->createMock(APIContent::class));

        $repositoryMock->expects(self::once())->method('commit');

        // Execute
        $mockedService->createContent($contentCreateStruct, $locationCreateStructs);
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::buildSPILocationCreateStructs
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     */
    public function testCreateContentWithLocationsDuplicateUnderParent()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You provided multiple LocationCreateStructs with the same parent Location \'321\'');

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue'),
                ]
            ),
        ];

        $repositoryMock = $this->getRepositoryMock();
        $mockedService = $this->getPartlyMockedContentService();
        $locationServiceMock = $this->getLocationServiceMock();
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $permissionResolver = $this->getPermissionResolverMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock */
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        $spiLocationCreateStruct = new SPILocation\CreateStruct();
        $parentLocation = new Location(['id' => 321]);
        $locationCreateStruct = new LocationCreateStruct(['parentLocationId' => 321]);
        $locationCreateStructs = [$locationCreateStruct, clone $locationCreateStruct];
        $contentType = new ContentType(
            [
                'id' => 123,
                'fieldDefinitions' => new FieldDefinitionCollection($fieldDefinitions),
                'nameSchema' => '<nameSchema>',
            ]
        );
        $contentCreateStruct = new ContentCreateStruct(
            [
                'fields' => [
                    new Field([
                        'fieldDefIdentifier' => 'identifier',
                        'value' => 123,
                        'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                        'languageCode' => 'eng-US',
                    ]),
                ],
                'mainLanguageCode' => 'eng-US',
                'contentType' => $contentType,
                'alwaysAvailable' => false,
                'ownerId' => 169,
                'sectionId' => 1,
            ]
        );

        $languageHandlerMock->expects(self::any())
            ->method('loadByLanguageCode')
            ->with(self::isType('string'))
            ->will(
                self::returnCallback(
                    static function () {
                        return new Language(['id' => 4242]);
                    }
                )
            );

        $fieldTypeMock = $this->createMock(FieldType::class);
        $fieldTypeMock->expects(self::any())
            ->method('acceptValue')
            ->will(
                self::returnCallback(
                    static function ($valueString) {
                        return new ValueStub($valueString);
                    }
                )
            );

        $this->toHashFieldTypeMock($fieldTypeMock);
        $this->getFieldTypeFieldTypeRegistryMock($fieldTypeMock);

        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(self::equalTo($contentType->id))
            ->will(self::returnValue($contentType));

        $repositoryMock->expects(self::once())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));

        $that = $this;
        $permissionResolver->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('create'),
                self::isInstanceOf(APIContentCreateStruct::class),
                self::equalTo($locationCreateStructs)
            )->will(
                self::returnCallback(
                    static function () use ($that, $contentCreateStruct): bool {
                        $that->assertEquals($contentCreateStruct, func_get_arg(2));

                        return true;
                    }
                )
            );

        $domainMapperMock->expects(self::once())
            ->method('getUniqueHash')
            ->with(self::isInstanceOf(APIContentCreateStruct::class))
            ->will(
                self::returnCallback(
                    static function ($object) use ($that, $contentCreateStruct): string {
                        $that->assertEquals($contentCreateStruct, $object);

                        return 'hash';
                    }
                )
            );

        $locationServiceMock->expects(self::once())
            ->method('loadLocation')
            ->with(self::equalTo(321))
            ->will(self::returnValue($parentLocation));

        $repositoryMock->expects(self::any())
            ->method('getLocationService')
            ->will(self::returnValue($locationServiceMock));

        $domainMapperMock->expects(self::any())
            ->method('buildSPILocationCreateStruct')
            ->with(
                self::equalTo($locationCreateStruct),
                self::equalTo($parentLocation),
                self::equalTo(true),
                self::equalTo(null),
                self::equalTo(null),
                self::equalTo(false)
            )->will(self::returnValue($spiLocationCreateStruct));

        $fieldTypeMock = $this->createMock(SPIFieldType::class);
        $fieldTypeMock
            ->method('acceptValue')
            ->will(
                self::returnCallback(
                    static function ($valueString) {
                        return new ValueStub($valueString);
                    }
                )
            );

        $this->getFieldTypeRegistryMock()
            ->method('getFieldType')
            ->will(self::returnValue($fieldTypeMock));

        $mockedService->createContent(
            $contentCreateStruct,
            $locationCreateStructs
        );
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getDefaultObjectStates
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     */
    public function testCreateContentObjectStates()
    {
        $spiFields = [
            new SPIField(
                [
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('defaultValue'),
                    'languageCode' => 'eng-US',
                ]
            ),
        ];
        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue'),
                ]
            ),
        ];
        $objectStateGroups = [
            new SPIObjectStateGroup(['id' => 10]),
            new SPIObjectStateGroup(['id' => 20]),
        ];

        // Set up a simple case that will pass
        $contentCreateStruct = $this->assertForTestCreateContentNonRedundantFieldSet(
            'eng-US',
            [],
            $spiFields,
            $fieldDefinitions,
            [],
            true,
            // Do not execute
            false
        );
        $timestamp = time();
        $contentCreateStruct->modificationDate = new \DateTime("@{$timestamp}");

        $repositoryMock = $this->getRepositoryMock();
        $mockedService = $this->getPartlyMockedContentService();
        /** @var \PHPUnit\Framework\MockObject\MockObject $handlerMock */
        $handlerMock = $this->getPersistenceMock()->contentHandler();
        $domainMapperMock = $this->getContentDomainMapperMock();

        $this->mockGetDefaultObjectStates();
        $this->mockSetDefaultObjectStates();

        $spiContentCreateStruct = new SPIContentCreateStruct(
            [
                'name' => [],
                'typeId' => 123,
                'sectionId' => 1,
                'ownerId' => 169,
                'remoteId' => 'hash',
                'fields' => $spiFields,
                'modified' => $timestamp,
                'initialLanguageId' => 4242,
                'locations' => [],
            ]
        );
        $spiContentCreateStruct2 = clone $spiContentCreateStruct;
        ++$spiContentCreateStruct2->modified;

        $spiContent = new SPIContent(
            [
                'versionInfo' => new SPIContent\VersionInfo(
                    [
                        'contentInfo' => new SPIContent\ContentInfo(['id' => 42]),
                        'versionNo' => 7,
                    ]
                ),
            ]
        );

        $handlerMock->expects(self::once())
            ->method('create')
            ->with(self::equalTo($spiContentCreateStruct))
            ->will(self::returnValue($spiContent));

        $domainMapperMock->expects(self::once())
            ->method('buildContentDomainObject')
            ->with(
                self::isInstanceOf(SPIContent::class),
                self::isInstanceOf(APIContentType::class)
            )
            ->willReturn($this->createMock(APIContent::class));

        $repositoryMock->expects(self::once())->method('commit');

        // Execute
        $mockedService->createContent($contentCreateStruct, []);
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForCreate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getDefaultObjectStates
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @dataProvider providerForTestCreateContentThrowsContentValidationExceptionTranslation
     */
    public function testCreateContentWithRollback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Store failed');

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue'),
                ]
            ),
        ];

        // Setup a simple case that will pass
        $contentCreateStruct = $this->assertForTestCreateContentNonRedundantFieldSet(
            'eng-US',
            [],
            [],
            $fieldDefinitions,
            [],
            false,
            // Do not execute test
            false
        );

        $repositoryMock = $this->getRepositoryMock();
        $repositoryMock->expects(self::never())->method('commit');
        $repositoryMock->expects(self::once())->method('rollback');

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandlerMock */
        $contentHandlerMock = $this->getPersistenceMock()->contentHandler();
        $contentHandlerMock->expects(self::once())
            ->method('create')
            ->with(self::anything())
            ->will(self::throwException(new \Exception('Store failed')));

        // Execute
        $this->partlyMockedContentService->createContent($contentCreateStruct, []);
    }

    public function providerForTestUpdateContentThrowsBadStateException()
    {
        return [
            [VersionInfo::STATUS_PUBLISHED],
            [VersionInfo::STATUS_ARCHIVED],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentThrowsBadStateException
     */
    public function testUpdateContentThrowsBadStateException($status)
    {
        $this->expectException(BadStateException::class);

        $versionInfo = new VersionInfo(
            [
                'contentInfo' => new ContentInfo(['id' => 42]),
                'versionNo' => 7,
                'status' => $status,
            ]
        );
        $content = new Content(
            [
                'versionInfo' => $versionInfo,
                'internalFields' => [],
                'contentType' => new ContentType([]),
            ]
        );

        $mockedService = $this->getPartlyMockedContentService(['loadContent', 'internalLoadContentById']);
        $mockedService
            ->method('loadContent')
            ->with(
                self::equalTo(42),
                self::equalTo(null),
                self::equalTo(7)
            )->will(
                self::returnValue($content)
            );
        $mockedService
            ->method('internalLoadContentById')
            ->will(
                self::returnValue($content)
            );

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock
            ->method('canUser')
            ->will(self::returnValue(true));

        $contentUpdateStruct = new ContentUpdateStruct();

        $mockedService->updateContent($versionInfo, $contentUpdateStruct);
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     */
    public function testUpdateContentThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $permissionResolverMock = $this->getPermissionResolverMock();
        $mockedService = $this->getPartlyMockedContentService(['loadContent']);
        $contentUpdateStruct = new ContentUpdateStruct();
        $versionInfo = new VersionInfo(
            [
                'contentInfo' => new ContentInfo(['id' => 42]),
                'versionNo' => 7,
                'status' => VersionInfo::STATUS_DRAFT,
            ]
        );
        $content = new Content(
            [
                'versionInfo' => $versionInfo,
                'internalFields' => [],
                'contentType' => new ContentType([]),
            ]
        );

        $mockedService->expects(self::once())
            ->method('loadContent')
            ->with(
                self::equalTo(42),
                self::equalTo(null),
                self::equalTo(7)
            )->will(
                self::returnValue($content)
            );

        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('edit'),
                self::equalTo($content),
                self::isType('array')
            )->will(self::returnValue(false));

        $mockedService->updateContent($versionInfo, $contentUpdateStruct);
    }

    /**
     * @param string $initialLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     * @param string[] $existingLanguages
     *
     * @return string[]
     */
    protected function determineLanguageCodesForUpdate($initialLanguageCode, array $structFields, $existingLanguages)
    {
        $languageCodes = array_fill_keys($existingLanguages, true);
        if ($initialLanguageCode !== null) {
            $languageCodes[$initialLanguageCode] = true;
        }

        foreach ($structFields as $field) {
            if ($field->languageCode === null || isset($languageCodes[$field->languageCode])) {
                continue;
            }

            $languageCodes[$field->languageCode] = true;
        }

        return array_keys($languageCodes);
    }

    /**
     * @param string $initialLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     * @param string $mainLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition[] $fieldDefinitions
     *
     * @return array
     */
    protected function mapStructFieldsForUpdate($initialLanguageCode, $structFields, $mainLanguageCode, $fieldDefinitions)
    {
        $initialLanguageCode = $initialLanguageCode ?: $mainLanguageCode;

        $mappedFieldDefinitions = [];
        foreach ($fieldDefinitions as $fieldDefinition) {
            $mappedFieldDefinitions[$fieldDefinition->identifier] = $fieldDefinition;
        }

        $mappedStructFields = [];
        foreach ($structFields as $structField) {
            $identifier = $structField->fieldDefIdentifier;

            if ($structField->languageCode !== null) {
                $languageCode = $structField->languageCode;
            } elseif ($mappedFieldDefinitions[$identifier]->isTranslatable) {
                $languageCode = $initialLanguageCode;
            } else {
                $languageCode = $mainLanguageCode;
            }

            $mappedStructFields[$identifier][$languageCode] = (string)$structField->value;
        }

        return $mappedStructFields;
    }

    /**
     * Returns full, possibly redundant array of field values, indexed by field definition
     * identifier and language code.
     *
     * @param string $initialLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     * @param \Ibexa\Core\Repository\Values\Content\Content $content
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition[] $fieldDefinitions
     * @param array $languageCodes
     *
     * @return array
     */
    protected function determineValuesForUpdate(
        $initialLanguageCode,
        array $structFields,
        Content $content,
        array $fieldDefinitions,
        array $languageCodes
    ) {
        $mainLanguageCode = $content->versionInfo->contentInfo->mainLanguageCode;

        $mappedStructFields = $this->mapStructFieldsForUpdate(
            $initialLanguageCode,
            $structFields,
            $mainLanguageCode,
            $fieldDefinitions
        );

        $values = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $identifier = $fieldDefinition->identifier;
            foreach ($languageCodes as $languageCode) {
                if (!$fieldDefinition->isTranslatable) {
                    if (isset($mappedStructFields[$identifier][$mainLanguageCode])) {
                        $values[$identifier][$languageCode] = $mappedStructFields[$identifier][$mainLanguageCode];
                    } else {
                        $values[$identifier][$languageCode] = (string)$content->fields[$identifier][$mainLanguageCode];
                    }
                    continue;
                }

                if (isset($mappedStructFields[$identifier][$languageCode])) {
                    $values[$identifier][$languageCode] = $mappedStructFields[$identifier][$languageCode];
                    continue;
                }

                if (isset($content->fields[$identifier][$languageCode])) {
                    $values[$identifier][$languageCode] = (string)$content->fields[$identifier][$languageCode];
                    continue;
                }

                $values[$identifier][$languageCode] = (string)$fieldDefinition->defaultValue;
            }
        }

        return $this->stubValues($values);
    }

    protected function stubValues(array $fieldValues)
    {
        foreach ($fieldValues as &$languageValues) {
            foreach ($languageValues as &$value) {
                $value = new ValueStub($value);
            }
        }

        return $fieldValues;
    }

    /**
     * Asserts that calling updateContent() with given API field set causes calling
     * Handler::updateContent() with given SPI field set.
     *
     * @param string $initialLanguageCode
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $structFields
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field[] $spiFields
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $existingFields
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition[] $fieldDefinitions
     * @param bool $execute
     *
     * @return mixed
     */
    protected function assertForTestUpdateContentNonRedundantFieldSet(
        $initialLanguageCode,
        array $structFields,
        array $spiFields,
        array $existingFields,
        array $fieldDefinitions,
        $execute = true
    ) {
        $repositoryMock = $this->getRepositoryMock();
        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock
            ->expects(self::once())
            ->method('getCurrentUserReference')
            ->willReturn(new UserReference(169));
        $mockedService = $this->getPartlyMockedContentService(['internalLoadContentById']);
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandlerMock */
        $contentHandlerMock = $this->getPersistenceMock()->contentHandler();
        /** @var \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock */
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $domainMapperMock = $this->getContentDomainMapperMock();
        $relationProcessorMock = $this->getRelationProcessorMock();
        $nameSchemaServiceMock = $this->getNameSchemaServiceMock();
        $fieldTypeMock = $this->createMock(SPIFieldType::class);
        $existingLanguageCodes = array_map(
            static function (Field $field) {
                return $field->languageCode;
            },
            $existingFields
        );
        $languageCodes = $this->determineLanguageCodesForUpdate(
            $initialLanguageCode,
            $structFields,
            $existingLanguageCodes
        );
        $versionInfo = new VersionInfo(
            [
                'contentInfo' => new ContentInfo(
                    [
                        'id' => 42,
                        'contentTypeId' => 24,
                        'mainLanguageCode' => 'eng-GB',
                    ]
                ),
                'versionNo' => 7,
                'languageCodes' => $existingLanguageCodes,
                'status' => VersionInfo::STATUS_DRAFT,
            ]
        );

        $contentType = new ContentType([
            'fieldDefinitions' => new FieldDefinitionCollection($fieldDefinitions),
        ]);

        $content = new Content(
            [
                'versionInfo' => $versionInfo,
                'internalFields' => $existingFields,
                'contentType' => $contentType,
            ]
        );

        $languageHandlerMock->expects(self::any())
            ->method('loadByLanguageCode')
            ->with(self::isType('string'))
            ->will(
                self::returnCallback(
                    static function () {
                        return new Language(['id' => 4242]);
                    }
                )
            );

        $mockedService
            ->method('internalLoadContentById')
            ->with(
                self::equalTo(42),
                self::equalTo(null),
                self::equalTo(7)
            )->will(
                self::returnValue($content)
            );

        $repositoryMock->expects(self::once())->method('beginTransaction');

        $permissionResolverMock->expects(self::any())
            ->method('canUser')
            ->will(self::returnValue(true));

        $contentTypeServiceMock->expects(self::once())
            ->method('loadContentType')
            ->with(self::equalTo(24))
            ->will(self::returnValue($contentType));

        $repositoryMock->expects(self::once())
            ->method('getContentTypeService')
            ->will(self::returnValue($contentTypeServiceMock));

        $fieldTypeMock->expects(self::any())
            ->method('acceptValue')
            ->will(
                self::returnCallback(
                    static function ($value) {
                        return $value instanceof SPIValue
                            ? $value
                            : new ValueStub($value);
                    }
                )
            );

        $this->toHashFieldTypeMock($fieldTypeMock);

        $emptyValue = new ValueStub(self::EMPTY_FIELD_VALUE);
        $fieldTypeMock->expects(self::any())
            ->method('toPersistenceValue')
            ->will(
                self::returnCallback(
                    static function (ValueStub $value): string {
                        return (string)$value;
                    }
                )
            );

        $fieldTypeMock->expects(self::any())
            ->method('isEmptyValue')
            ->will(
                self::returnCallback(
                    static function (SPIValue $value) use ($emptyValue): bool {
                        return (string)$emptyValue === (string)$value;
                    }
                )
            );

        $fieldTypeMock->expects(self::any())
            ->method('validate')
            ->will(self::returnValue([]));

        $this->getFieldTypeFieldTypeRegistryMock($fieldTypeMock);

        $relationProcessorMock
            ->expects(self::exactly(count($fieldDefinitions) * count($languageCodes)))
            ->method('appendFieldRelations')
            ->with(
                self::isType('array'),
                self::isType('array'),
                self::isInstanceOf(SPIFieldType::class),
                self::isInstanceOf(Value::class),
                self::anything()
            );

        $values = $this->determineValuesForUpdate(
            $initialLanguageCode,
            $structFields,
            $content,
            $fieldDefinitions,
            $languageCodes
        );
        $nameSchemaServiceMock->expects(self::once())
            ->method('resolveContentNameSchema')
            ->with(
                self::equalTo($content),
                self::equalTo($values),
                self::equalTo($languageCodes)
            )->will(self::returnValue([]));

        $existingRelations = ['RELATIONS!!!'];
        $repositoryMock->method('sudo')->willReturn($existingRelations);
        $relationProcessorMock->expects(self::any())
            ->method('processFieldRelations')
            ->with(
                self::isType('array'),
                self::equalTo(42),
                self::isType('int'),
                self::equalTo($contentType),
                self::equalTo($existingRelations)
            );

        $contentUpdateStruct = new ContentUpdateStruct(
            [
                'fields' => $structFields,
                'initialLanguageCode' => $initialLanguageCode,
            ]
        );

        if ($execute) {
            $spiContentUpdateStruct = new SPIContentUpdateStruct(
                [
                    'creatorId' => 169,
                    'fields' => $spiFields,
                    'modificationDate' => time(),
                    'initialLanguageId' => 4242,
                ]
            );

            // During code coverage runs, timestamp might differ 1-3 seconds
            $spiContentUpdateStructTs1 = clone $spiContentUpdateStruct;
            ++$spiContentUpdateStructTs1->modificationDate;

            $spiContentUpdateStructTs2 = clone $spiContentUpdateStructTs1;
            ++$spiContentUpdateStructTs2->modificationDate;

            $spiContentUpdateStructTs3 = clone $spiContentUpdateStructTs2;
            ++$spiContentUpdateStructTs3->modificationDate;

            $spiContent = new SPIContent(
                [
                    'versionInfo' => new SPIContent\VersionInfo(
                        [
                            'contentInfo' => new SPIContent\ContentInfo(['id' => 42]),
                            'versionNo' => 7,
                        ]
                    ),
                ]
            );

            $contentHandlerMock->expects(self::once())
                ->method('updateContent')
                ->with(
                    42,
                    7,
                    self::logicalOr($spiContentUpdateStruct, $spiContentUpdateStructTs1, $spiContentUpdateStructTs2, $spiContentUpdateStructTs3)
                )
                ->will(self::returnValue($spiContent));

            $repositoryMock->expects(self::once())->method('commit');
            $domainMapperMock
                ->method('buildContentDomainObject')
                ->with(
                    self::isInstanceOf(SPIContent::class),
                    self::isInstanceOf(APIContentType::class)
                )
                ->will(self::returnValue($content));

            $mockedService->updateContent($content->versionInfo, $contentUpdateStruct);
        }

        return [$content->versionInfo, $contentUpdateStruct];
    }

    public function providerForTestUpdateContentNonRedundantFieldSet1()
    {
        $spiFields = [
            new SPIField(
                [
                    'id' => '100',
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue'),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
        ];

        return [
            // With languages set
            [
                'eng-GB',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields,
            ],
            // Without languages set
            [
                null,
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue'),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields,
            ],
            // Adding new language without fields
            [
                'eng-US',
                [],
                [],
            ],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * Testing the simplest use case.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentNonRedundantFieldSet1
     */
    public function testUpdateContentNonRedundantFieldSet1($initialLanguageCode, $structFields, $spiFields)
    {
        $existingFields = [
            new Field(
                [
                    'id' => '100',
                    'fieldDefIdentifier' => 'identifier',
                    'value' => new ValueStub('id100'),
                    'languageCode' => 'eng-GB',
                ]
            ),
        ];

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue'),
                ]
            ),
        ];

        $this->assertForTestUpdateContentNonRedundantFieldSet(
            $initialLanguageCode,
            $structFields,
            $spiFields,
            $existingFields,
            $fieldDefinitions
        );
    }

    public function providerForTestUpdateContentNonRedundantFieldSet2()
    {
        $spiFields0 = [
            new SPIField(
                [
                    'id' => '100',
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue'),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
        ];
        $spiFields1 = [
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];
        $spiFields2 = [
            new SPIField(
                [
                    'id' => 100,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue2'),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];

        return [
            // 0. With languages set
            [
                'eng-GB',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 1. Without languages set
            [
                null,
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue'),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 2. New language with language set
            [
                'eng-GB',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                ],
                $spiFields1,
            ],
            // 3. New language without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue'),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields1,
            ],
            // 4. New language and existing language with language set
            [
                'eng-GB',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields2,
            ],
            // 5. New language and existing language without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields2,
            ],
            // 6. Adding new language without fields
            [
                'eng-US',
                [],
                [
                    new SPIField(
                        [
                            'id' => null,
                            'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID,
                            'type' => 'fieldTypeIdentifier',
                            'value' => new ValueStub('defaultValue'),
                            'languageCode' => 'eng-US',
                            'versionNo' => 7,
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * Testing with translatable field.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentNonRedundantFieldSet2
     */
    public function testUpdateContentNonRedundantFieldSet2($initialLanguageCode, $structFields, $spiFields)
    {
        $existingFields = [
            new Field(
                [
                    'id' => '100',
                    'fieldDefIdentifier' => 'identifier',
                    'value' => new ValueStub('id100'),
                    'languageCode' => 'eng-GB',
                ]
            ),
        ];

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue'),
                ]
            ),
        ];

        $this->assertForTestUpdateContentNonRedundantFieldSet(
            $initialLanguageCode,
            $structFields,
            $spiFields,
            $existingFields,
            $fieldDefinitions
        );
    }

    public function providerForTestUpdateContentNonRedundantFieldSet3()
    {
        $spiFields0 = [
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];
        $spiFields1 = [
            new SPIField(
                [
                    'id' => 100,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue2'),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];
        $spiFields2 = [
            new SPIField(
                [
                    'id' => 100,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue2'),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => 101,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue3'),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
        ];
        $spiFields3 = [
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('defaultValue1'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];

        return [
            // 0. ew language with language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 1. New language without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 2. New language and existing language with language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields1,
            ],
            // 3. New language and existing language without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields1,
            ],
            // 4. New language and existing language with untranslatable field, with language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'value' => new ValueStub('newValue3'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields2,
            ],
            // 5. New language and existing language with untranslatable field, without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue3'),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields2,
            ],
            // 6. Adding new language without fields
            [
                'eng-US',
                [],
                $spiFields3,
            ],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * Testing with new language and untranslatable field.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentNonRedundantFieldSet3
     */
    public function testUpdateContentNonRedundantFieldSet3($initialLanguageCode, $structFields, $spiFields)
    {
        $existingFields = [
            new Field(
                [
                    'id' => '100',
                    'fieldDefIdentifier' => 'identifier1',
                    'value' => new ValueStub('id100'),
                    'languageCode' => 'eng-GB',
                ]
            ),
            new Field(
                [
                    'id' => '101',
                    'fieldDefIdentifier' => 'identifier2',
                    'value' => new ValueStub('id101'),
                    'languageCode' => 'eng-GB',
                ]
            ),
        ];

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier1',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue1'),
                ]
            ),
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier2',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue2'),
                ]
            ),
        ];

        $this->assertForTestUpdateContentNonRedundantFieldSet(
            $initialLanguageCode,
            $structFields,
            $spiFields,
            $existingFields,
            $fieldDefinitions
        );
    }

    public function providerForTestUpdateContentNonRedundantFieldSet4()
    {
        $spiFields0 = [
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];
        $spiFields1 = [
            new SPIField(
                [
                    'id' => 100,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];
        $spiFields2 = [
            new SPIField(
                [
                    'id' => 100,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
        ];

        return [
            // 0. New translation with empty field by default
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 1. New translation with empty field by default, without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 2. New translation with empty field given
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 3. New translation with empty field given, without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 4. Updating existing language with empty value
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields1,
            ],
            // 5. Updating existing language with empty value, without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1'),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields1,
            ],
            // 6. Updating existing language with empty value and adding new language with empty value
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields2,
            ],
            // 7. Updating existing language with empty value and adding new language with empty value,
            // without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields2,
            ],
            // 8. Adding new language with no fields given
            [
                'eng-US',
                [],
                [],
            ],
            // 9. Adding new language with fields
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                ],
                [],
            ],
            // 10. Adding new language with fields, without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => null,
                        ]
                    ),
                ],
                [],
            ],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * Testing with empty values.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentNonRedundantFieldSet4
     */
    public function testUpdateContentNonRedundantFieldSet4($initialLanguageCode, $structFields, $spiFields)
    {
        $existingFields = [
            new Field(
                [
                    'id' => '100',
                    'fieldDefIdentifier' => 'identifier1',
                    'value' => new ValueStub('id100'),
                    'languageCode' => 'eng-GB',
                ]
            ),
            new Field(
                [
                    'id' => '101',
                    'fieldDefIdentifier' => 'identifier2',
                    'value' => new ValueStub('id101'),
                    'languageCode' => 'eng-GB',
                ]
            ),
        ];

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier1',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier2',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
        ];

        $this->assertForTestUpdateContentNonRedundantFieldSet(
            $initialLanguageCode,
            $structFields,
            $spiFields,
            $existingFields,
            $fieldDefinitions
        );
    }

    /**
     * @return array
     *
     * @todo add first field empty
     */
    public function providerForTestUpdateContentNonRedundantFieldSetComplex()
    {
        $spiFields0 = [
            new SPIField(
                [
                    'id' => 100,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1-eng-GB'),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_D,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue4'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];
        $spiFields1 = [
            new SPIField(
                [
                    'id' => 100,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1-eng-GB'),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue2'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_D,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('defaultValue4'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];
        $spiFields2 = [
            new SPIField(
                [
                    'id' => 100,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue1-eng-GB'),
                    'languageCode' => 'eng-GB',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('newValue2'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_D,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('defaultValue4'),
                    'languageCode' => 'ger-DE',
                    'versionNo' => 7,
                ]
            ),
            new SPIField(
                [
                    'id' => null,
                    'fieldDefinitionId' => self::EXAMPLE_FIELD_DEFINITION_ID_D,
                    'type' => 'fieldTypeIdentifier',
                    'value' => new ValueStub('defaultValue4'),
                    'languageCode' => 'eng-US',
                    'versionNo' => 7,
                ]
            ),
        ];

        return [
            // 0. Add new language and update existing
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier4',
                            'value' => new ValueStub('newValue4'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1-eng-GB'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 1. Add new language and update existing, without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier4',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue4'),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1-eng-GB'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields0,
            ],
            // 2. Add new language and update existing variant
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1-eng-GB'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields1,
            ],
            // 3. Add new language and update existing variant, without language set
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => null,
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1-eng-GB'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields1,
            ],
            // 4. Update with multiple languages
            [
                'ger-DE',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'value' => new ValueStub('newValue1-eng-GB'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
                $spiFields2,
            ],
            // 5. Update with multiple languages without language set
            [
                'ger-DE',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier2',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue2'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier1',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub('newValue1-eng-GB'),
                            'languageCode' => null,
                        ]
                    ),
                ],
                $spiFields2,
            ],
        ];
    }

    protected function fixturesForTestUpdateContentNonRedundantFieldSetComplex()
    {
        $existingFields = [
            new Field(
                [
                    'id' => '100',
                    'fieldDefIdentifier' => 'identifier1',
                    'value' => new ValueStub('initialValue1'),
                    'languageCode' => 'eng-GB',
                ]
            ),
            new Field(
                [
                    'id' => '101',
                    'fieldDefIdentifier' => 'identifier2',
                    'value' => new ValueStub('initialValue2'),
                    'languageCode' => 'eng-GB',
                ]
            ),
            new Field(
                [
                    'id' => '102',
                    'fieldDefIdentifier' => 'identifier3',
                    'value' => new ValueStub('initialValue3'),
                    'languageCode' => 'eng-GB',
                ]
            ),
            new Field(
                [
                    'id' => '103',
                    'fieldDefIdentifier' => 'identifier4',
                    'value' => new ValueStub('initialValue4'),
                    'languageCode' => 'eng-GB',
                ]
            ),
        ];

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier1',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_B,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier2',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_C,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier3',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue3'),
                ]
            ),
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_D,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier4',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue4'),
                ]
            ),
        ];

        return [$existingFields, $fieldDefinitions];
    }

    /**
     * Test for the updateContent() method.
     *
     * Testing more complex cases.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentNonRedundantFieldSetComplex
     */
    public function testUpdateContentNonRedundantFieldSetComplex($initialLanguageCode, $structFields, $spiFields)
    {
        list($existingFields, $fieldDefinitions) = $this->fixturesForTestUpdateContentNonRedundantFieldSetComplex();

        $this->assertForTestUpdateContentNonRedundantFieldSet(
            $initialLanguageCode,
            $structFields,
            $spiFields,
            $existingFields,
            $fieldDefinitions
        );
    }

    public function providerForTestUpdateContentWithInvalidLanguage()
    {
        return [
            [
                'eng-GB',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => $this->createMock(Value::class),
                            'languageCode' => 'Klingon',
                        ]
                    ),
                ],
            ],
            [
                'Klingon',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => $this->createMock(Value::class),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentWithInvalidLanguage
     */
    public function testUpdateContentWithInvalidLanguage($initialLanguageCode, $structFields)
    {
        $this->expectException(APINotFoundException::class);
        $this->expectExceptionMessage('Could not find \'Language\' with identifier \'Klingon\'');

        $permissionResolverMock = $this->getPermissionResolverMock();
        $mockedService = $this->getPartlyMockedContentService(['loadContent', 'internalLoadContentById']);
        $fieldTypeMock = $this->createMock(SPIFieldType::class);
        /** @var \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock */
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        $versionInfo = new VersionInfo(
            [
                'contentInfo' => new ContentInfo(
                    [
                        'id' => 42,
                        'contentTypeId' => 24,
                        'mainLanguageCode' => 'eng-GB',
                    ]
                ),
                'versionNo' => 7,
                'languageCodes' => ['eng-GB'],
                'status' => VersionInfo::STATUS_DRAFT,
            ]
        );

        $fieldValueMock = $this->createMock(Value::class);

        $content = new Content(
            [
                'versionInfo' => $versionInfo,
                'internalFields' => [
                    new Field([
                        'fieldDefIdentifier' => 'identifier',
                        'value' => $fieldValueMock,
                        'languageCode' => 'eng-GB',
                    ]),
                ],
                'contentType' => new ContentType([
                    'fieldDefinitions' => new FieldDefinitionCollection([
                        new FieldDefinition([
                            'identifier' => 'identifier',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'defaultValue' => $fieldValueMock,
                        ]),
                    ]),
                ]),
            ]
        );

        $fieldTypeMock->expects(self::any())
            ->method('acceptValue')->will(self::returnValue($fieldValueMock));

        $this->getFieldTypeRegistryMock()->expects(self::any())
            ->method('getFieldType')->will(self::returnValue($fieldTypeMock));

        $languageHandlerMock->expects(self::any())
            ->method('loadByLanguageCode')
            ->with(self::isType('string'))
            ->will(
                self::returnCallback(
                    static function ($languageCode) {
                        if ($languageCode === 'Klingon') {
                            throw new NotFoundException('Language', 'Klingon');
                        }

                        return new Language(['id' => 4242]);
                    }
                )
            );

        $mockedService
            ->method('loadContent')
            ->with(
                self::equalTo(42),
                self::equalTo(null),
                self::equalTo(7)
            )->will(
                self::returnValue($content)
            );

        $mockedService
            ->method('internalLoadContentById')
            ->will(
                self::returnValue($content)
            );

        $permissionResolverMock
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('edit'),
                self::equalTo($content),
                self::isType('array')
            )->will(self::returnValue(true));

        $contentUpdateStruct = new ContentUpdateStruct(
            [
                'fields' => $structFields,
                'initialLanguageCode' => $initialLanguageCode,
            ]
        );

        $mockedService->updateContent($content->versionInfo, $contentUpdateStruct);
    }

    protected function assertForUpdateContentContentValidationException(
        $initialLanguageCode,
        $structFields,
        $fieldDefinitions = []
    ) {
        $permissionResolverMock = $this->getPermissionResolverMock();
        $mockedService = $this->getPartlyMockedContentService(['internalLoadContentById', 'loadContent']);
        /** @var \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock */
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        $versionInfo = new VersionInfo(
            [
                'contentInfo' => new ContentInfo(
                    [
                        'id' => 42,
                        'contentTypeId' => 24,
                        'mainLanguageCode' => 'eng-GB',
                    ]
                ),
                'versionNo' => 7,
                'languageCodes' => ['eng-GB'],
                'status' => VersionInfo::STATUS_DRAFT,
            ]
        );
        $contentType = new ContentType([
            'fieldDefinitions' => new FieldDefinitionCollection($fieldDefinitions),
        ]);
        $content = new Content(
            [
                'versionInfo' => $versionInfo,
                'internalFields' => [],
                'contentType' => $contentType,
            ]
        );

        $fieldTypeMock = $this->createMock(FieldType::class);

        $fieldTypeMock
            ->method('acceptValue')
            ->will(
                self::returnCallback(
                    static function ($value) {
                        return $value instanceof SPIValue
                            ? $value
                            : new ValueStub($value);
                    }
                )
            );

        $this->toHashFieldTypeMock($fieldTypeMock);

        $this->getFieldTypeRegistryMock()->expects(self::any())
            ->method('getFieldType')->will(self::returnValue($fieldTypeMock));

        $languageHandlerMock->expects(self::any())
            ->method('loadByLanguageCode')
            ->with(self::isType('string'))
            ->will(
                self::returnCallback(
                    static function ($languageCode) {
                        if ($languageCode === 'Klingon') {
                            throw new NotFoundException('Language', 'Klingon');
                        }

                        return new Language(['id' => 4242]);
                    }
                )
            );

        $mockedService
            ->method('loadContent')
            ->with(
                self::equalTo(42),
                self::equalTo(null),
                self::equalTo(7)
            )->will(
                self::returnValue($content)
            );

        $mockedService
            ->method('internalLoadContentById')
            ->will(
                self::returnValue($content)
            );

        $permissionResolverMock->expects(self::any())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('edit'),
                self::equalTo($content),
                self::isType('array')
            )->will(self::returnValue(true));

        /*
        $contentTypeServiceMock->expects($this->once())
            ->method('loadContentType')
            ->with($this->equalTo($contentType->id))
            ->will($this->returnValue($contentType));
        */

        $contentUpdateStruct = new ContentUpdateStruct(
            [
                'fields' => $structFields,
                'initialLanguageCode' => $initialLanguageCode,
            ]
        );

        $mockedService->updateContent($content->versionInfo, $contentUpdateStruct);
    }

    private function prepareContentForTestCreateAndUpdateContent(
        array $existingLanguageCodes,
        array $fieldDefinitions,
        array $existingFields
    ): Content {
        $versionInfo = new VersionInfo(
            [
                'contentInfo' => new ContentInfo(
                    [
                        'id' => 42,
                        'contentTypeId' => 24,
                        'mainLanguageCode' => 'eng-GB',
                    ]
                ),
                'versionNo' => 7,
                'languageCodes' => $existingLanguageCodes,
                'status' => VersionInfo::STATUS_DRAFT,
                'names' => [
                    'eng-GB' => 'Test',
                ],
                'initialLanguageCode' => 'eng-GB',
            ]
        );
        $contentType = new ContentType([
            'fieldDefinitions' => new FieldDefinitionCollection($fieldDefinitions),
        ]);

        return new Content(
            [
                'versionInfo' => $versionInfo,
                'internalFields' => $existingFields,
                'contentType' => $contentType,
            ]
        );
    }

    public function providerForTestUpdateContentThrowsContentValidationExceptionFieldDefinition()
    {
        return [
            [
                'eng-GB',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'eng-GB',
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentThrowsContentValidationExceptionFieldDefinition
     */
    public function testUpdateContentThrowsContentValidationExceptionFieldDefinition($initialLanguageCode, $structFields)
    {
        $this->expectException(ContentValidationException::class);
        $this->expectExceptionMessage('Field definition \'identifier\' does not exist in given content type');

        $this->assertForUpdateContentContentValidationException(
            $initialLanguageCode,
            $structFields,
            []
        );
    }

    public function providerForTestUpdateContentThrowsContentValidationExceptionTranslation()
    {
        return [
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'value' => new ValueStub('newValue'),
                            'languageCode' => 'eng-US',
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentThrowsContentValidationExceptionTranslation
     */
    public function testUpdateContentThrowsContentValidationExceptionTranslation($initialLanguageCode, $structFields)
    {
        $this->expectException(ContentValidationException::class);
        $this->expectExceptionMessage('You cannot set a value for the non-translatable Field definition \'identifier\' in language \'eng-US\'');

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID_A,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub(self::EMPTY_FIELD_VALUE),
                ]
            ),
        ];

        $this->assertForUpdateContentContentValidationException(
            $initialLanguageCode,
            $structFields,
            $fieldDefinitions
        );
    }

    public function assertForTestUpdateContentRequiredField(
        $initialLanguageCode,
        $structFields,
        $existingFields,
        $fieldDefinitions
    ) {
        $permissionResolver = $this->getPermissionResolverMock();
        $mockedService = $this->getPartlyMockedContentService(['internalLoadContentById', 'loadContent']);
        /** @var \PHPUnit\Framework\MockObject\MockObject $languageHandlerMock */
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        $fieldTypeMock = $this->createMock(SPIFieldType::class);
        $existingLanguageCodes = array_map(
            static function (Field $field) {
                return $field->languageCode;
            },
            $existingFields
        );

        $content = $this->prepareContentForTestCreateAndUpdateContent(
            $existingLanguageCodes,
            $fieldDefinitions,
            $existingFields
        );

        $this->loadByLanguageCodeMock($languageHandlerMock);

        $mockedService
            ->method('loadContent')
            ->with(
                self::equalTo(42),
                self::equalTo(null),
                self::equalTo(7)
            )->will(
                self::returnValue($content)
            );

        $mockedService
            ->method('internalLoadContentById')
            ->will(
                self::returnValue($content)
            );

        $permissionResolver->expects(self::any())
            ->method('canUser')
            ->with(
                self::equalTo('content'),
                self::equalTo('edit'),
                self::equalTo($content),
                self::isType('array')
            )->will(self::returnValue(true));

        $this->acceptFieldTypeValueMock($fieldTypeMock);

        $this->isEmptyValueFieldTypeMock($fieldTypeMock);

        $fieldTypeMock->expects(self::any())
            ->method('validate')
            ->with(
                self::isInstanceOf(APIFieldDefinition::class),
                self::isInstanceOf(Value::class)
            );

        $this->getFieldTypeRegistryMock()->expects(self::any())
            ->method('getFieldType')
            ->will(self::returnValue($fieldTypeMock));

        $contentUpdateStruct = new ContentUpdateStruct(
            [
                'fields' => $structFields,
                'initialLanguageCode' => $initialLanguageCode,
            ]
        );

        return [$content->versionInfo, $contentUpdateStruct];
    }

    public function providerForTestUpdateContentRequiredField()
    {
        return [
            [
                'eng-US',
                [
                    new Field(
                        [
                            'fieldDefIdentifier' => 'identifier',
                            'fieldTypeIdentifier' => self::EXAMPLE_FIELD_TYPE_IDENTIFIER,
                            'value' => new ValueStub(self::EMPTY_FIELD_VALUE),
                            'languageCode' => null,
                        ]
                    ),
                ],
                'identifier',
                'eng-US',
            ],
        ];
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentRequiredField
     */
    public function testUpdateContentRequiredField(
        $initialLanguageCode,
        $structFields,
        $identifier,
        $languageCode
    ) {
        $this->expectException(ContentFieldValidationException::class);

        $existingFields = [
            new Field(
                [
                    'id' => '100',
                    'fieldDefIdentifier' => 'identifier',
                    'value' => new ValueStub('initialValue'),
                    'languageCode' => 'eng-GB',
                ]
            ),
        ];
        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => true,
                    'identifier' => 'identifier',
                    'isRequired' => true,
                    'defaultValue' => new ValueStub('defaultValue'),
                ]
            ),
        ];
        list($versionInfo, $contentUpdateStruct) =
            $this->assertForTestUpdateContentRequiredField(
                $initialLanguageCode,
                $structFields,
                $existingFields,
                $fieldDefinitions
            );

        try {
            $this->partlyMockedContentService->updateContent($versionInfo, $contentUpdateStruct);
        } catch (ContentValidationException $e) {
            self::assertEquals(
                "Value for required field definition '{$identifier}' with language '{$languageCode}' is empty",
                $e->getMessage()
            );

            throw $e;
        }
    }

    public function assertForTestUpdateContentThrowsContentFieldValidationException(
        $initialLanguageCode,
        $structFields,
        $existingFields,
        $fieldDefinitions,
        array $allFieldErrors
    ): array {
        $permissionResolverMock = $this->getPermissionResolverMock();
        $mockedService = $this->getPartlyMockedContentService(['internalLoadContentById', 'loadContent']);
        $languageHandlerMock = $this->getPersistenceMock()->contentLanguageHandler();
        $fieldTypeMock = $this->createMock(SPIFieldType::class);
        $existingLanguageCodes = array_map(
            static function (Field $field) {
                return $field->languageCode;
            },
            $existingFields
        );

        $content = $this->prepareContentForTestCreateAndUpdateContent($existingLanguageCodes, $fieldDefinitions, $existingFields);

        $this->loadByLanguageCodeMock($languageHandlerMock);

        $mockedService
            ->method('internalLoadContentById')
            ->will(
                self::returnValue($content)
            );

        $mockedService
            ->method('loadContent')
            ->with(
                self::equalTo(42),
                self::equalTo(null),
                self::equalTo(7)
            )
            ->will(
                self::returnValue($content)
            );

        $permissionResolverMock
            ->method('canUser')
            ->will(self::returnValue(true));

        $emptyValue = new ValueStub(self::EMPTY_FIELD_VALUE);

        $fieldTypeMock
            ->method('acceptValue')
            ->will(
                self::returnCallback(
                    static function ($value) {
                        return $value instanceof SPIValue
                            ? $value
                            : new ValueStub($value);
                    }
                )
            );

        $fieldTypeMock
            ->method('isEmptyValue')
            ->will(
                self::returnCallback(
                    static function (ValueStub $value) use ($emptyValue): bool {
                        return (string)$emptyValue === (string)$value;
                    }
                )
            );

        $fieldTypeMock
            ->expects(self::any())
            ->method('validate')
            ->will(
                self::returnCallback(
                    static function (FieldDefinition $fieldDefinition) use ($allFieldErrors, $structFields) {
                        foreach ($structFields as $structField) {
                            if ($structField->fieldDefIdentifier !== $fieldDefinition->identifier) {
                                continue;
                            }

                            return $allFieldErrors[$fieldDefinition->id][$structField->languageCode] ?? null;
                        }

                        return null;
                    }
                )
            );

        $this->getFieldTypeRegistryMock()->expects(self::any())
            ->method('getFieldType')
            ->will(self::returnValue($fieldTypeMock));

        $contentUpdateStruct = new ContentUpdateStruct(
            [
                'fields' => $structFields,
                'initialLanguageCode' => $initialLanguageCode,
            ]
        );

        return [$content->versionInfo, $contentUpdateStruct, $allFieldErrors];
    }

    public function providerForTestUpdateContentThrowsContentFieldValidationException(): array
    {
        $newValue1engGBValidationError = new ValidationError('newValue1-eng-GB');
        $newValue2ValidationError = new ValidationError('newValue2');
        $newValue4ValidationError = new ValidationError('newValue4');

        $allFieldErrors = [
            [
                self::EXAMPLE_FIELD_DEFINITION_ID_A => [
                    'eng-GB' => $newValue1engGBValidationError,
                    'eng-US' => $newValue1engGBValidationError,
                ],
                self::EXAMPLE_FIELD_DEFINITION_ID_D => [
                    'eng-GB' => $newValue4ValidationError,
                    'eng-US' => $newValue4ValidationError,
                ],
            ],
            [
                self::EXAMPLE_FIELD_DEFINITION_ID_A => [
                    'eng-GB' => $newValue1engGBValidationError,
                    'eng-US' => $newValue1engGBValidationError,
                ],
            ],
            [
                self::EXAMPLE_FIELD_DEFINITION_ID_A => [
                    'eng-GB' => $newValue1engGBValidationError,
                    'eng-US' => $newValue1engGBValidationError,
                ],
                self::EXAMPLE_FIELD_DEFINITION_ID_B => [
                    'eng-GB' => $newValue2ValidationError,
                    'eng-US' => $newValue2ValidationError,
                ],
            ],
            [
                self::EXAMPLE_FIELD_DEFINITION_ID_A => [
                    'eng-GB' => $newValue1engGBValidationError,
                    'eng-US' => $newValue1engGBValidationError,
                ],
            ],
            [
                self::EXAMPLE_FIELD_DEFINITION_ID_A => [
                    'eng-GB' => $newValue1engGBValidationError,
                    'ger-DE' => $newValue1engGBValidationError,
                    'eng-US' => $newValue1engGBValidationError,
                ],
                self::EXAMPLE_FIELD_DEFINITION_ID_B => [
                    'eng-GB' => $newValue2ValidationError,
                    'eng-US' => $newValue2ValidationError,
                ],
            ],
            [
                self::EXAMPLE_FIELD_DEFINITION_ID_B => [
                    'eng-US' => $newValue2ValidationError,
                ],
            ],
        ];

        $data = $this->providerForTestUpdateContentNonRedundantFieldSetComplex();
        $count = count($data);
        for ($i = 0; $i < $count; ++$i) {
            $data[$i][] = $allFieldErrors[$i];
        }

        return $data;
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     *
     * @dataProvider providerForTestUpdateContentThrowsContentFieldValidationException
     */
    public function testUpdateContentThrowsContentFieldValidationException(
        $initialLanguageCode,
        $structFields,
        $spiField,
        $allFieldErrors
    ): void {
        $this->expectException(ContentFieldValidationException::class);
        $this->expectExceptionMessage('Content "Test" fields did not validate');

        list($existingFields, $fieldDefinitions) = $this->fixturesForTestUpdateContentNonRedundantFieldSetComplex();
        list($versionInfo, $contentUpdateStruct) =
            $this->assertForTestUpdateContentThrowsContentFieldValidationException(
                $initialLanguageCode,
                $structFields,
                $existingFields,
                $fieldDefinitions,
                $allFieldErrors
            );

        try {
            $this->partlyMockedContentService->updateContent($versionInfo, $contentUpdateStruct);
        } catch (ContentFieldValidationException $e) {
            self::assertEquals($allFieldErrors, $e->getFieldErrors());
            throw $e;
        }
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getLanguageCodesForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::mapFieldsForUpdate
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent
     */
    public function testUpdateContentTransactionRollback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Store failed');

        $existingFields = [
            new Field(
                [
                    'id' => '100',
                    'fieldDefIdentifier' => 'identifier',
                    'value' => new ValueStub('initialValue'),
                    'languageCode' => 'eng-GB',
                ]
            ),
        ];

        $fieldDefinitions = [
            new FieldDefinition(
                [
                    'id' => self::EXAMPLE_FIELD_DEFINITION_ID,
                    'fieldTypeIdentifier' => 'fieldTypeIdentifier',
                    'isTranslatable' => false,
                    'identifier' => 'identifier',
                    'isRequired' => false,
                    'defaultValue' => new ValueStub('defaultValue'),
                ]
            ),
        ];

        // Setup a simple case that will pass
        list($versionInfo, $contentUpdateStruct) = $this->assertForTestUpdateContentNonRedundantFieldSet(
            'eng-US',
            [],
            [],
            $existingFields,
            $fieldDefinitions,
            // Do not execute test
            false
        );

        $repositoryMock = $this->getRepositoryMock();
        $repositoryMock->expects(self::never())->method('commit');
        $repositoryMock->expects(self::once())->method('rollback');

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandlerMock */
        $contentHandlerMock = $this->getPersistenceMock()->contentHandler();
        $contentHandlerMock->expects(self::once())
            ->method('updateContent')
            ->with(
                self::anything(),
                self::anything(),
                self::anything()
            )->will(self::throwException(new \Exception('Store failed')));

        // Execute
        $this->partlyMockedContentService->updateContent($versionInfo, $contentUpdateStruct);
    }

    /**
     * Test for the copyContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::copyContent
     */
    public function testCopyContentThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepositoryMock();
        $contentService = $this->getPartlyMockedContentService(['internalLoadContentInfo']);
        $contentInfo = $this->createMock(APIContentInfo::class);
        $locationCreateStruct = new LocationCreateStruct();
        $locationCreateStruct->parentLocationId = 1;
        $location = new Location(['id' => $locationCreateStruct->parentLocationId]);
        $locationServiceMock = $this->getLocationServiceMock();
        $permissionResolver = $this->getPermissionResolverMock();

        $repository->expects(self::once())
            ->method('getLocationService')
            ->will(self::returnValue($locationServiceMock));

        $locationServiceMock->expects(self::once())
            ->method('loadLocation')
            ->with(
                $locationCreateStruct->parentLocationId
            )
            ->will(self::returnValue($location));

        $contentInfo->expects(self::any())
            ->method('__get')
            ->with('sectionId')
            ->will(self::returnValue(42));

        $destinationLocationTarget = (new DestinationLocation($locationCreateStruct->parentLocationId, $contentInfo));
        $permissionResolver
            ->method('canUser')
            ->with(
                'content',
                'create',
                $contentInfo,
                [$location, $destinationLocationTarget]
            )
            ->will(self::returnValue(false));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo */
        $contentService->copyContent($contentInfo, $locationCreateStruct);
    }

    /**
     * Test for the copyContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::copyContent
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getDefaultObjectStates
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::internalPublishVersion
     */
    public function testCopyContent()
    {
        $repositoryMock = $this->getRepositoryMock();
        $contentService = $this->getPartlyMockedContentService([
            'internalLoadContentInfo',
            'internalLoadContentById',
            'loadContentByVersionInfo',
            'getUnixTimestamp',
        ]);
        $locationServiceMock = $this->getLocationServiceMock();
        $contentInfoMock = $this->createMock(APIContentInfo::class);
        $locationCreateStruct = new LocationCreateStruct();
        $locationCreateStruct->parentLocationId = 2;
        $location = new Location(['id' => $locationCreateStruct->parentLocationId]);
        $user = $this->getStubbedUser(14);

        $permissionResolverMock = $this->getPermissionResolverMock();

        $permissionResolverMock
            ->method('getCurrentUserReference')
            ->willReturn($user);

        $repositoryMock
            ->method('getPermissionResolver')
            ->willReturn($permissionResolverMock);

        $repositoryMock->expects(self::exactly(3))
            ->method('getLocationService')
            ->will(self::returnValue($locationServiceMock));

        $locationServiceMock->expects(self::once())
            ->method('loadLocation')
            ->with($locationCreateStruct->parentLocationId)
            ->will(self::returnValue($location));

        $contentInfoMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['isHidden', true],
                        ['id', 42],
                    ]
                )
            );
        $versionInfoMock = $this->createMock(APIVersionInfo::class);

        $versionInfoMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['versionNo', 123],
                    ]
                )
            );

        $versionInfoMock->expects(self::once())
            ->method('isDraft')
            ->willReturn(true);

        $versionInfoMock
            ->method('getContentInfo')
            ->will(self::returnValue($contentInfoMock));

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandlerMock */
        $contentHandlerMock = $this->getPersistenceMock()->contentHandler();
        $domainMapperMock = $this->getContentDomainMapperMock();

        $repositoryMock->expects(self::once())->method('beginTransaction');
        $repositoryMock->expects(self::once())->method('commit');

        $destinationLocationTarget = (new DestinationLocation($locationCreateStruct->parentLocationId, $contentInfoMock));
        $permissionResolverMock
            ->method('canUser')
            ->withConsecutive(
                ['content', 'create', $contentInfoMock, [$location, $destinationLocationTarget]],
                ['content', 'manage_locations', $contentInfoMock, [$location]],
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $spiContentInfo = new SPIContentInfo(['id' => 42]);
        $spiVersionInfo = new SPIVersionInfo(
            [
                'contentInfo' => $spiContentInfo,
                'creationDate' => 123456,
            ]
        );
        $spiContent = new SPIContent(['versionInfo' => $spiVersionInfo]);
        $contentHandlerMock->expects(self::once())
            ->method('copy')
            ->with(42, null)
            ->will(self::returnValue($spiContent));

        $this->mockGetDefaultObjectStates();
        $this->mockSetDefaultObjectStates();

        $domainMapperMock->expects(self::once())
            ->method('buildVersionInfoDomainObject')
            ->with($spiVersionInfo)
            ->will(self::returnValue($versionInfoMock));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfoMock */
        $content = $this->mockPublishVersion(123456, 126666, true);
        $locationServiceMock->expects(self::once())
            ->method('createLocation')
            ->with(
                $content->getVersionInfo()->getContentInfo(),
                $locationCreateStruct
            );

        $contentService
            ->method('internalLoadContentById')
            ->with(
                $content->id
            )
            ->will(self::returnValue($content));

        $contentService->expects(self::once())
            ->method('getUnixTimestamp')
            ->will(self::returnValue(126666));

        $contentService
            ->method('loadContentByVersionInfo')
            ->will(self::returnValue($content));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfoMock */
        $contentService->copyContent($contentInfoMock, $locationCreateStruct, null);
    }

    /**
     * Test for the copyContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::copyContent
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getDefaultObjectStates
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::internalPublishVersion
     */
    public function testCopyContentWithVersionInfo()
    {
        $repositoryMock = $this->getRepositoryMock();
        $contentService = $this->getPartlyMockedContentService([
            'internalLoadContentById',
            'getUnixTimestamp',
        ]);
        $locationServiceMock = $this->getLocationServiceMock();
        $contentInfoMock = $this->createMock(APIContentInfo::class);
        $locationCreateStruct = new LocationCreateStruct();
        $locationCreateStruct->parentLocationId = 2;
        $location = new Location(['id' => $locationCreateStruct->parentLocationId]);
        $user = $this->getStubbedUser(14);

        $permissionResolverMock = $this->getPermissionResolverMock();

        $permissionResolverMock
            ->method('getCurrentUserReference')
            ->willReturn($user);

        $repositoryMock
            ->method('getPermissionResolver')
            ->willReturn($permissionResolverMock);

        $repositoryMock->expects(self::exactly(3))
            ->method('getLocationService')
            ->will(self::returnValue($locationServiceMock));

        $locationServiceMock->expects(self::once())
            ->method('loadLocation')
            ->with($locationCreateStruct->parentLocationId)
            ->will(self::returnValue($location));

        $contentInfoMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap([
                    ['isHidden', true],
                    ['id', 42],
                ])
            );

        $versionInfoMock = $this->createMock(APIVersionInfo::class);

        $versionInfoMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['versionNo', 123],
                    ]
                )
            );
        $versionInfoMock->expects(self::once())
            ->method('isDraft')
            ->willReturn(true);
        $versionInfoMock
            ->method('getContentInfo')
            ->will(self::returnValue($contentInfoMock));

        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandlerMock */
        $contentHandlerMock = $this->getPersistenceMock()->contentHandler();
        $domainMapperMock = $this->getContentDomainMapperMock();

        $repositoryMock->expects(self::once())->method('beginTransaction');
        $repositoryMock->expects(self::once())->method('commit');

        $destinationLocationTarget = (new DestinationLocation($locationCreateStruct->parentLocationId, $contentInfoMock));
        $permissionResolverMock
            ->method('canUser')
            ->withConsecutive(
                ['content', 'create', $contentInfoMock, [$location, $destinationLocationTarget]],
                ['content', 'manage_locations', $contentInfoMock, [$location]],
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $spiContentInfo = new SPIContentInfo(['id' => 42]);
        $spiVersionInfo = new SPIVersionInfo(
            [
                'contentInfo' => $spiContentInfo,
                'creationDate' => 123456,
            ]
        );
        $spiContent = new SPIContent(['versionInfo' => $spiVersionInfo]);
        $contentHandlerMock->expects(self::once())
            ->method('copy')
            ->with(42, 123)
            ->will(self::returnValue($spiContent));

        $this->mockGetDefaultObjectStates();
        $this->mockSetDefaultObjectStates();

        $domainMapperMock->expects(self::once())
            ->method('buildVersionInfoDomainObject')
            ->with($spiVersionInfo)
            ->will(self::returnValue($versionInfoMock));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfoMock */
        $content = $this->mockPublishVersion(123456, 126666, true);
        $locationServiceMock->expects(self::once())
            ->method('createLocation')
            ->with(
                $content->getVersionInfo()->getContentInfo(),
                $locationCreateStruct
            );

        $contentService
            ->method('internalLoadContentById')
            ->with(
                $content->id
            )
            ->will(self::returnValue($content));

        $contentService->expects(self::once())
            ->method('getUnixTimestamp')
            ->will(self::returnValue(126666));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfoMock */
        $contentService->copyContent($contentInfoMock, $locationCreateStruct, $versionInfoMock);
    }

    /**
     * Test for the copyContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::copyContent
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::getDefaultObjectStates
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::internalPublishVersion
     */
    public function testCopyContentWithRollback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Handler threw an exception');

        $repositoryMock = $this->getRepositoryMock();
        $contentService = $this->getPartlyMockedContentService();
        /** @var \PHPUnit\Framework\MockObject\MockObject $contentHandlerMock */
        $contentHandlerMock = $this->getPersistenceMock()->contentHandler();
        $locationCreateStruct = new LocationCreateStruct();
        $locationCreateStruct->parentLocationId = 2;
        $location = new Location(['id' => $locationCreateStruct->parentLocationId]);
        $locationServiceMock = $this->getLocationServiceMock();
        $user = $this->getStubbedUser(14);

        $permissionResolverMock = $this->getPermissionResolverMock();

        $permissionResolverMock
            ->method('getCurrentUserReference')
            ->willReturn($user);

        $repositoryMock
            ->method('getPermissionResolver')
            ->willReturn($permissionResolverMock);

        $repositoryMock->expects(self::once())
            ->method('getLocationService')
            ->will(self::returnValue($locationServiceMock));

        $locationServiceMock->expects(self::once())
            ->method('loadLocation')
            ->with($locationCreateStruct->parentLocationId)
            ->will(self::returnValue($location));

        $contentInfoMock = $this->createMock(APIContentInfo::class);
        $contentInfoMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(42));

        $this->mockGetDefaultObjectStates();

        $repositoryMock->expects(self::once())->method('beginTransaction');
        $repositoryMock->expects(self::once())->method('rollback');

        $destinationLocationTarget = (new DestinationLocation($locationCreateStruct->parentLocationId, $contentInfoMock));
        $permissionResolverMock
            ->method('canUser')
            ->withConsecutive(
                ['content', 'create', $contentInfoMock, [$location, $destinationLocationTarget]],
                ['content', 'manage_locations', $contentInfoMock, [$location]],
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $contentHandlerMock->expects(self::once())
            ->method('copy')
            ->with(42, null)
            ->will(self::throwException(new Exception('Handler threw an exception')));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfoMock */
        $contentService->copyContent($contentInfoMock, $locationCreateStruct, null);
    }

    /**
     * Reusable method for setting exceptions on buildContentDomainObject usage.
     *
     * Plain usage as in when content type is loaded directly.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content $spiContent
     * @param array $translations
     * @param bool $useAlwaysAvailable
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    private function mockBuildContentDomainObject(SPIContent $spiContent, ?array $translations = null, ?bool $useAlwaysAvailable = null)
    {
        $contentTypeId = $spiContent->versionInfo->contentInfo->contentTypeId;
        $contentTypeServiceMock = $this->getContentTypeServiceMock();
        $repositoryMock = $this->getRepositoryMock();

        $contentType = new ContentType([
            'id' => $contentTypeId,
            'fieldDefinitions' => new FieldDefinitionCollection([]),
        ]);

        $repositoryMock->expects(self::once())
            ->method('getContentTypeService')
            ->willReturn($contentTypeServiceMock);

        $contentTypeServiceMock
            ->method('loadContentType')
            ->with(self::equalTo($contentTypeId))
            ->willReturn($contentType);

        $content = $this->createMock(APIContent::class);
        $content->method('getContentType')
            ->willReturn($contentType);

        $this->getContentDomainMapperMock()
            ->expects(self::once())
            ->method('buildContentDomainObject')
            ->with($spiContent, $contentType, $translations ?? [], $useAlwaysAvailable)
            ->willReturn($content);

        return $content;
    }

    protected function mockGetDefaultObjectStates()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject $objectStateHandlerMock */
        $objectStateHandlerMock = $this->getPersistenceMock()->objectStateHandler();

        $objectStateGroups = [
            new SPIObjectStateGroup(['id' => 10]),
            new SPIObjectStateGroup(['id' => 20]),
        ];

        /* @var \PHPUnit\Framework\MockObject\MockObject $objectStateHandlerMock */
        $objectStateHandlerMock->expects(self::once())
            ->method('loadAllGroups')
            ->will(self::returnValue($objectStateGroups));

        $objectStateHandlerMock->expects(self::at(1))
            ->method('loadObjectStates')
            ->with(self::equalTo(10))
            ->will(
                self::returnValue(
                    [
                        new SPIObjectState(['id' => 11, 'groupId' => 10]),
                        new SPIObjectState(['id' => 12, 'groupId' => 10]),
                    ]
                )
            );

        $objectStateHandlerMock->expects(self::at(2))
            ->method('loadObjectStates')
            ->with(self::equalTo(20))
            ->will(
                self::returnValue(
                    [
                        new SPIObjectState(['id' => 21, 'groupId' => 20]),
                        new SPIObjectState(['id' => 22, 'groupId' => 20]),
                    ]
                )
            );
    }

    protected function mockSetDefaultObjectStates()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject $objectStateHandlerMock */
        $objectStateHandlerMock = $this->getPersistenceMock()->objectStateHandler();

        $defaultObjectStates = [
            new SPIObjectState(['id' => 11, 'groupId' => 10]),
            new SPIObjectState(['id' => 21, 'groupId' => 20]),
        ];
        foreach ($defaultObjectStates as $index => $objectState) {
            $objectStateHandlerMock->expects(self::at($index + 3))
                ->method('setContentState')
                ->with(
                    42,
                    $objectState->groupId,
                    $objectState->id
                );
        }
    }

    /**
     * @param int|null $publicationDate
     * @param int|null $modificationDate
     * @param bool $isHidden
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    protected function mockPublishVersion($publicationDate = null, $modificationDate = null, $isHidden = false)
    {
        $versionInfoMock = $this->createMock(APIVersionInfo::class);
        $contentInfoMock = $this->createMock(APIContentInfo::class);
        /* @var \PHPUnit\Framework\MockObject\MockObject $contentHandlerMock */
        $contentHandlerMock = $this->getPersistenceMock()->contentHandler();
        $metadataUpdateStruct = new SPIMetadataUpdateStruct();

        $spiContent = new SPIContent([
            'versionInfo' => new VersionInfo([
                'contentInfo' => new ContentInfo(['id' => 42, 'contentTypeId' => 123]),
            ]),
            'fields' => new FieldDefinitionCollection([]),
        ]);

        $contentMock = $this->mockBuildContentDomainObject($spiContent);
        $contentMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['id', 42],
                        ['contentInfo', $contentInfoMock],
                        ['versionInfo', $versionInfoMock],
                    ]
                )
            );
        $contentMock->expects(self::any())
            ->method('getVersionInfo')
            ->will(self::returnValue($versionInfoMock));
        $versionInfoMock->expects(self::any())
            ->method('getContentInfo')
            ->will(self::returnValue($contentInfoMock));
        $versionInfoMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['languageCodes', ['eng-GB']],
                    ]
                )
            );
        $contentInfoMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['alwaysAvailable', true],
                        ['mainLanguageCode', 'eng-GB'],
                    ]
                )
            );

        $currentTime = time();
        if ($publicationDate === null && $versionInfoMock->versionNo === 1) {
            $publicationDate = $currentTime;
        }

        // Account for 1 second of test execution time
        $metadataUpdateStruct->publicationDate = $publicationDate;
        $metadataUpdateStruct->modificationDate = $modificationDate ?? $currentTime;
        $metadataUpdateStruct->isHidden = $isHidden;

        $contentHandlerMock->expects(self::once())
            ->method('publish')
            ->with(
                42,
                123,
                $metadataUpdateStruct
            )
            ->will(self::returnValue($spiContent));

        /* @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $contentMock */
        $this->mockPublishUrlAliasesForContent($contentMock);

        return $contentMock;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     */
    protected function mockPublishUrlAliasesForContent(APIContent $content)
    {
        $nameSchemaServiceMock = $this->getNameSchemaServiceMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject $urlAliasHandlerMock */
        $urlAliasHandlerMock = $this->getPersistenceMock()->urlAliasHandler();
        $locationServiceMock = $this->getLocationServiceMock();
        $location = $this->createMock(APILocation::class);

        $location->expects(self::at(0))
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(123));
        $location->expects(self::at(1))
            ->method('__get')
            ->with('parentLocationId')
            ->will(self::returnValue(456));

        $urlAliasNames = ['eng-GB' => 'hello'];
        $nameSchemaServiceMock->expects(self::once())
            ->method('resolveUrlAliasSchema')
            ->with($content)
            ->will(self::returnValue($urlAliasNames));

        $locationServiceMock->expects(self::once())
            ->method('loadLocations')
            ->with($content->getVersionInfo()->getContentInfo())
            ->will(self::returnValue([$location]));

        $urlAliasHandlerMock->expects(self::once())
            ->method('publishUrlAliasForLocation')
            ->with(123, 456, 'hello', 'eng-GB', true, true);

        $location->expects(self::at(2))
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(123));

        $location->expects(self::at(3))
            ->method('__get')
            ->with('parentLocationId')
            ->will(self::returnValue(456));

        $urlAliasHandlerMock->expects(self::once())
            ->method('archiveUrlAliasesForDeletedTranslations')
            ->with(123, 456, ['eng-GB']);
    }

    protected $relationProcessorMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Repository\Helper\RelationProcessor
     */
    protected function getRelationProcessorMock()
    {
        if (!isset($this->relationProcessorMock)) {
            $this->relationProcessorMock = $this->createMock(RelationProcessor::class);
        }

        return $this->relationProcessorMock;
    }

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * &\Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface
     */
    protected NameSchemaServiceInterface $nameSchemaServiceMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     * &\Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface
     */
    protected function getNameSchemaServiceMock(): NameSchemaServiceInterface
    {
        if (!isset($this->nameSchemaServiceMock)) {
            $this->nameSchemaServiceMock = $this->createMock(NameSchemaServiceInterface::class);
        }

        return $this->nameSchemaServiceMock;
    }

    protected $contentTypeServiceMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Contracts\Core\Repository\ContentTypeService
     */
    protected function getContentTypeServiceMock()
    {
        if (!isset($this->contentTypeServiceMock)) {
            $this->contentTypeServiceMock = $this->createMock(APIContentTypeService::class);
        }

        return $this->contentTypeServiceMock;
    }

    protected $locationServiceMock;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Contracts\Core\Repository\LocationService
     */
    protected function getLocationServiceMock()
    {
        if (!isset($this->locationServiceMock)) {
            $this->locationServiceMock = $this->createMock(APILocationService::class);
        }

        return $this->locationServiceMock;
    }

    /** @var \Ibexa\Core\Repository\ContentService */
    protected $partlyMockedContentService;

    /**
     * Returns the content service to test with $methods mocked.
     *
     * Injected Repository comes from {@see getRepositoryMock()} and persistence handler from {@see getPersistenceMock()}
     *
     * @param string[] $methods
     *
     * @return \Ibexa\Core\Repository\ContentService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPartlyMockedContentService(?array $methods = null, int $gracePeriodInSeconds = 0)
    {
        if (!isset($this->partlyMockedContentService)) {
            $this->partlyMockedContentService = $this->getMockBuilder(ContentService::class)
                ->setMethods($methods)
                ->setConstructorArgs(
                    [
                        $this->getRepositoryMock(),
                        $this->getPersistenceMock(),
                        $this->getContentDomainMapperMock(),
                        $this->getRelationProcessorMock(),
                        $this->getNameSchemaServiceMock(),
                        $this->getFieldTypeRegistryMock(),
                        $this->getPermissionServiceMock(),
                        $this->getContentMapper(),
                        $this->getContentValidatorStrategy(),
                        $this->getContentFilteringHandlerMock(),
                        new ContentCollector(),
                        $this->createMock(ValidatorInterface::class),
                        [
                            'grace_period_in_seconds' => $gracePeriodInSeconds,
                        ],
                    ]
                )
                ->getMock();
        }

        return $this->partlyMockedContentService;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Repository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getRepositoryMock(): Repository
    {
        $repositoryMock = parent::getRepositoryMock();
        $repositoryMock
            ->expects(self::any())
            ->method('getPermissionResolver')
            ->willReturn($this->getPermissionResolverMock());

        return $repositoryMock;
    }
}
