<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Decorator\ContentServiceDecorator;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentDraftList;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentMetadataUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContentServiceDecoratorTest extends TestCase
{
    private const EXAMPLE_CONTENT_ID = 1;
    private const EXAMPLE_LANGUAGE_CODE = 'eng-GB';
    private const EXAMPLE_CONTENT_REMOTE_ID = 'example';
    private const EXAMPLE_VERSION_NO = 1;

    protected function createDecorator(ContentService & MockObject $service): ContentService
    {
        return new class($service) extends ContentServiceDecorator {};
    }

    protected function createServiceMock(): ContentService & MockObject
    {
        return $this->createMock(ContentService::class);
    }

    public function testLoadContentInfoDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::EXAMPLE_CONTENT_ID];

        $serviceMock->expects($this->once())->method('loadContentInfo')->with(...$parameters)->willReturn(self::createStub(ContentInfo::class));

        $decoratedService->loadContentInfo(...$parameters);
    }

    public function testLoadContentInfoListDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [[9999]];

        $serviceMock->expects($this->once())->method('loadContentInfoList')->with(...$parameters)->willReturn([]);

        $decoratedService->loadContentInfoList(...$parameters);
    }

    public function testLoadContentInfoByRemoteIdDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = ['random_value_5ced05ce1541a6.54558542'];

        $serviceMock->expects($this->once())->method('loadContentInfoByRemoteId')->with(...$parameters)->willReturn(self::createStub(ContentInfo::class));

        $decoratedService->loadContentInfoByRemoteId(...$parameters);
    }

    public function testLoadVersionInfoDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(ContentInfo::class),
            self::EXAMPLE_VERSION_NO,
        ];

        $serviceMock->expects($this->once())->method('loadVersionInfo')->with(...$parameters)->willReturn(self::createStub(VersionInfo::class));

        $decoratedService->loadVersionInfo(...$parameters);
    }

    public function testLoadVersionInfoByIdDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::EXAMPLE_CONTENT_ID,
            self::EXAMPLE_VERSION_NO,
        ];

        $serviceMock->expects($this->once())->method('loadVersionInfoById')->with(...$parameters)->willReturn(self::createStub(VersionInfo::class));

        $decoratedService->loadVersionInfoById(...$parameters);
    }

    public function testLoadContentByContentInfoDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(ContentInfo::class),
            [self::EXAMPLE_LANGUAGE_CODE],
            self::EXAMPLE_VERSION_NO,
            true,
        ];

        $serviceMock->expects($this->once())->method('loadContentByContentInfo')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->loadContentByContentInfo(...$parameters);
    }

    public function testLoadContentByVersionInfoDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(VersionInfo::class),
            [self::EXAMPLE_LANGUAGE_CODE],
            true,
        ];

        $serviceMock->expects($this->once())->method('loadContentByVersionInfo')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->loadContentByVersionInfo(...$parameters);
    }

    public function testLoadContentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::EXAMPLE_CONTENT_ID,
            [self::EXAMPLE_LANGUAGE_CODE],
            self::EXAMPLE_VERSION_NO,
            true,
        ];

        $serviceMock->expects($this->once())->method('loadContent')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->loadContent(...$parameters);
    }

    public function testLoadContentByRemoteIdDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::EXAMPLE_CONTENT_REMOTE_ID,
            [self::EXAMPLE_LANGUAGE_CODE],
            self::EXAMPLE_VERSION_NO,
            true,
        ];

        $serviceMock->expects($this->once())->method('loadContentByRemoteId')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->loadContentByRemoteId(...$parameters);
    }

    public function testLoadContentListByContentInfoDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            [self::createStub(ContentInfo::class)],
            [self::EXAMPLE_LANGUAGE_CODE],
            true,
        ];

        $serviceMock->expects($this->once())->method('loadContentListByContentInfo')->with(...$parameters)->willReturn([]);

        $decoratedService->loadContentListByContentInfo(...$parameters);
    }

    public function testCreateContentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(ContentCreateStruct::class),
            ['random_value_5ced05ce155881.06739513'],
        ];

        $serviceMock->expects($this->once())->method('createContent')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->createContent(...$parameters);
    }

    public function testUpdateContentMetadataDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(ContentInfo::class),
            self::createStub(ContentMetadataUpdateStruct::class),
        ];

        $serviceMock->expects($this->once())->method('updateContentMetadata')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->updateContentMetadata(...$parameters);
    }

    public function testDeleteContentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::createStub(ContentInfo::class)];

        $serviceMock->expects($this->once())->method('deleteContent')->with(...$parameters)->willReturn([]);

        $decoratedService->deleteContent(...$parameters);
    }

    public function testCreateContentDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(ContentInfo::class),
            self::createStub(VersionInfo::class),
            self::createStub(User::class),
        ];

        $serviceMock->expects($this->once())->method('createContentDraft')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->createContentDraft(...$parameters);
    }

    public function testLoadContentDraftListDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::createStub(User::class)];

        $serviceMock->expects($this->once())->method('loadContentDraftList')->with(...$parameters)->willReturn(new ContentDraftList());

        $decoratedService->loadContentDraftList(...$parameters);
    }

    public function testUpdateContentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(VersionInfo::class),
            self::createStub(ContentUpdateStruct::class),
        ];

        $serviceMock->expects($this->once())->method('updateContent')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->updateContent(...$parameters);
    }

    public function testPublishVersionDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::createStub(VersionInfo::class)];

        $serviceMock->expects($this->once())->method('publishVersion')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->publishVersion(...$parameters);
    }

    public function testDeleteVersionDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::createStub(VersionInfo::class)];

        $serviceMock->expects($this->once())->method('deleteVersion')->with(...$parameters);

        $decoratedService->deleteVersion(...$parameters);
    }

    public function testLoadVersionsDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::createStub(ContentInfo::class)];

        $serviceMock->expects($this->once())->method('loadVersions')->with(...$parameters)->willReturn([]);

        $decoratedService->loadVersions(...$parameters);
    }

    public function testCopyContentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(ContentInfo::class),
            self::createStub(LocationCreateStruct::class),
            self::createStub(VersionInfo::class),
        ];

        $serviceMock->expects($this->once())->method('copyContent')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->copyContent(...$parameters);
    }

    public function testLoadRelationListDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::createStub(VersionInfo::class)];

        $serviceMock->expects($this->once())->method('loadRelationList')->with(...$parameters)->willReturn(new RelationList());

        $decoratedService->loadRelationList(...$parameters);
    }

    public function testLoadReverseRelationsDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::createStub(ContentInfo::class)];

        $serviceMock->expects($this->once())->method('loadReverseRelations')->with(...$parameters)->willReturn([]);

        $decoratedService->loadReverseRelations(...$parameters);
    }

    public function testAddRelationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(VersionInfo::class),
            self::createStub(ContentInfo::class),
        ];

        $serviceMock->expects($this->once())->method('addRelation')->with(...$parameters)->willReturn(self::createStub(Relation::class));

        $decoratedService->addRelation(...$parameters);
    }

    public function testDeleteRelationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(VersionInfo::class),
            self::createStub(ContentInfo::class),
        ];

        $serviceMock->expects($this->once())->method('deleteRelation')->with(...$parameters);

        $decoratedService->deleteRelation(...$parameters);
    }

    public function testDeleteTranslationDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(ContentInfo::class),
            self::EXAMPLE_LANGUAGE_CODE,
        ];

        $serviceMock->expects($this->once())->method('deleteTranslation')->with(...$parameters);

        $decoratedService->deleteTranslation(...$parameters);
    }

    public function testDeleteTranslationFromDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(VersionInfo::class),
            'random_value_5ced05ce156d37.22902273',
        ];

        $serviceMock->expects($this->once())->method('deleteTranslationFromDraft')->with(...$parameters)->willReturn(self::createStub(Content::class));

        $decoratedService->deleteTranslationFromDraft(...$parameters);
    }

    public function testHideContentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::createStub(ContentInfo::class)];

        $serviceMock->expects($this->once())->method('hideContent')->with(...$parameters);

        $decoratedService->hideContent(...$parameters);
    }

    public function testRevealContentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::createStub(ContentInfo::class)];

        $serviceMock->expects($this->once())->method('revealContent')->with(...$parameters);

        $decoratedService->revealContent(...$parameters);
    }

    public function testNewContentCreateStructDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::createStub(ContentType::class),
            'random_value_5ced05ce156db7.87562997',
        ];

        $serviceMock->expects($this->once())->method('newContentCreateStruct')->with(...$parameters)->willReturn(self::createStub(ContentCreateStruct::class));

        $decoratedService->newContentCreateStruct(...$parameters);
    }

    public function testNewContentMetadataUpdateStructDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [];

        $serviceMock->expects($this->once())->method('newContentMetadataUpdateStruct')->with(...$parameters)->willReturn(self::createStub(ContentMetadataUpdateStruct::class));

        $decoratedService->newContentMetadataUpdateStruct(...$parameters);
    }

    public function testNewContentUpdateStructDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [];

        $serviceMock->expects($this->once())->method('newContentUpdateStruct')->with(...$parameters)->willReturn(self::createStub(ContentUpdateStruct::class));

        $decoratedService->newContentUpdateStruct(...$parameters);
    }

    /**
     * @throws BadStateException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function testLoadVersionInfoListByContentInfoDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $argument = [self::createStub(ContentInfo::class)];

        $serviceMock
            ->expects($this->once())
            ->method('loadVersionInfoListByContentInfo')
            ->with($argument)
            ->willReturn([]);

        $decoratedService->loadVersionInfoListByContentInfo($argument);
    }
}
