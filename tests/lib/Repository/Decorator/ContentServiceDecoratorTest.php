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

    public function testLoadContentInfoDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::EXAMPLE_CONTENT_ID];

        $serviceMock->expects(self::once())->method('loadContentInfo')->with(...$parameters)->willReturn($this->createMock(ContentInfo::class));

        $decoratedService->loadContentInfo(...$parameters);
    }

    public function testLoadContentInfoListDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [[9999]];

        $serviceMock->expects(self::once())->method('loadContentInfoList')->with(...$parameters)->willReturn([]);

        $decoratedService->loadContentInfoList(...$parameters);
    }

    public function testLoadContentInfoByRemoteIdDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = ['random_value_5ced05ce1541a6.54558542'];

        $serviceMock->expects(self::once())->method('loadContentInfoByRemoteId')->with(...$parameters)->willReturn($this->createMock(ContentInfo::class));

        $decoratedService->loadContentInfoByRemoteId(...$parameters);
    }

    public function testLoadVersionInfoDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentInfo::class),
            self::EXAMPLE_VERSION_NO,
        ];

        $serviceMock->expects(self::once())->method('loadVersionInfo')->with(...$parameters)->willReturn($this->createMock(VersionInfo::class));

        $decoratedService->loadVersionInfo(...$parameters);
    }

    public function testLoadVersionInfoByIdDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::EXAMPLE_CONTENT_ID,
            self::EXAMPLE_VERSION_NO,
        ];

        $serviceMock->expects(self::once())->method('loadVersionInfoById')->with(...$parameters)->willReturn($this->createMock(VersionInfo::class));

        $decoratedService->loadVersionInfoById(...$parameters);
    }

    public function testLoadContentByContentInfoDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentInfo::class),
            [self::EXAMPLE_LANGUAGE_CODE],
            self::EXAMPLE_VERSION_NO,
            true,
        ];

        $serviceMock->expects(self::once())->method('loadContentByContentInfo')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->loadContentByContentInfo(...$parameters);
    }

    public function testLoadContentByVersionInfoDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(VersionInfo::class),
            [self::EXAMPLE_LANGUAGE_CODE],
            true,
        ];

        $serviceMock->expects(self::once())->method('loadContentByVersionInfo')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->loadContentByVersionInfo(...$parameters);
    }

    public function testLoadContentDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::EXAMPLE_CONTENT_ID,
            [self::EXAMPLE_LANGUAGE_CODE],
            self::EXAMPLE_VERSION_NO,
            true,
        ];

        $serviceMock->expects(self::once())->method('loadContent')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->loadContent(...$parameters);
    }

    public function testLoadContentByRemoteIdDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            self::EXAMPLE_CONTENT_REMOTE_ID,
            [self::EXAMPLE_LANGUAGE_CODE],
            self::EXAMPLE_VERSION_NO,
            true,
        ];

        $serviceMock->expects(self::once())->method('loadContentByRemoteId')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->loadContentByRemoteId(...$parameters);
    }

    public function testLoadContentListByContentInfoDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            [$this->createMock(ContentInfo::class)],
            [self::EXAMPLE_LANGUAGE_CODE],
            true,
        ];

        $serviceMock->expects(self::once())->method('loadContentListByContentInfo')->with(...$parameters)->willReturn([]);

        $decoratedService->loadContentListByContentInfo(...$parameters);
    }

    public function testCreateContentDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentCreateStruct::class),
            ['random_value_5ced05ce155881.06739513'],
        ];

        $serviceMock->expects(self::once())->method('createContent')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->createContent(...$parameters);
    }

    public function testUpdateContentMetadataDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(ContentMetadataUpdateStruct::class),
        ];

        $serviceMock->expects(self::once())->method('updateContentMetadata')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->updateContentMetadata(...$parameters);
    }

    public function testDeleteContentDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(ContentInfo::class)];

        $serviceMock->expects(self::once())->method('deleteContent')->with(...$parameters)->willReturn([]);

        $decoratedService->deleteContent(...$parameters);
    }

    public function testCreateContentDraftDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(VersionInfo::class),
            $this->createMock(User::class),
        ];

        $serviceMock->expects(self::once())->method('createContentDraft')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->createContentDraft(...$parameters);
    }

    public function testLoadContentDraftListDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(User::class)];

        $serviceMock->expects(self::once())->method('loadContentDraftList')->with(...$parameters)->willReturn(new ContentDraftList());

        $decoratedService->loadContentDraftList(...$parameters);
    }

    public function testUpdateContentDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentUpdateStruct::class),
        ];

        $serviceMock->expects(self::once())->method('updateContent')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->updateContent(...$parameters);
    }

    public function testPublishVersionDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(VersionInfo::class)];

        $serviceMock->expects(self::once())->method('publishVersion')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->publishVersion(...$parameters);
    }

    public function testDeleteVersionDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(VersionInfo::class)];

        $serviceMock->expects(self::once())->method('deleteVersion')->with(...$parameters);

        $decoratedService->deleteVersion(...$parameters);
    }

    public function testLoadVersionsDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(ContentInfo::class)];

        $serviceMock->expects(self::once())->method('loadVersions')->with(...$parameters)->willReturn([]);

        $decoratedService->loadVersions(...$parameters);
    }

    public function testCopyContentDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(LocationCreateStruct::class),
            $this->createMock(VersionInfo::class),
        ];

        $serviceMock->expects(self::once())->method('copyContent')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->copyContent(...$parameters);
    }

    public function testLoadRelationListDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(VersionInfo::class)];

        $serviceMock->expects(self::once())->method('loadRelationList')->with(...$parameters)->willReturn(new RelationList());

        $decoratedService->loadRelationList(...$parameters);
    }

    public function testLoadReverseRelationsDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(ContentInfo::class)];

        $serviceMock->expects(self::once())->method('loadReverseRelations')->with(...$parameters)->willReturn([]);

        $decoratedService->loadReverseRelations(...$parameters);
    }

    public function testAddRelationDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentInfo::class),
        ];

        $serviceMock->expects(self::once())->method('addRelation')->with(...$parameters)->willReturn($this->createMock(Relation::class));

        $decoratedService->addRelation(...$parameters);
    }

    public function testDeleteRelationDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentInfo::class),
        ];

        $serviceMock->expects(self::once())->method('deleteRelation')->with(...$parameters);

        $decoratedService->deleteRelation(...$parameters);
    }

    public function testDeleteTranslationDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentInfo::class),
            self::EXAMPLE_LANGUAGE_CODE,
        ];

        $serviceMock->expects(self::once())->method('deleteTranslation')->with(...$parameters);

        $decoratedService->deleteTranslation(...$parameters);
    }

    public function testDeleteTranslationFromDraftDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(VersionInfo::class),
            'random_value_5ced05ce156d37.22902273',
        ];

        $serviceMock->expects(self::once())->method('deleteTranslationFromDraft')->with(...$parameters)->willReturn($this->createMock(Content::class));

        $decoratedService->deleteTranslationFromDraft(...$parameters);
    }

    public function testHideContentDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(ContentInfo::class)];

        $serviceMock->expects(self::once())->method('hideContent')->with(...$parameters);

        $decoratedService->hideContent(...$parameters);
    }

    public function testRevealContentDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(ContentInfo::class)];

        $serviceMock->expects(self::once())->method('revealContent')->with(...$parameters);

        $decoratedService->revealContent(...$parameters);
    }

    public function testNewContentCreateStructDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(ContentType::class),
            'random_value_5ced05ce156db7.87562997',
        ];

        $serviceMock->expects(self::once())->method('newContentCreateStruct')->with(...$parameters)->willReturn($this->createMock(ContentCreateStruct::class));

        $decoratedService->newContentCreateStruct(...$parameters);
    }

    public function testNewContentMetadataUpdateStructDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [];

        $serviceMock->expects(self::once())->method('newContentMetadataUpdateStruct')->with(...$parameters)->willReturn($this->createMock(ContentMetadataUpdateStruct::class));

        $decoratedService->newContentMetadataUpdateStruct(...$parameters);
    }

    public function testNewContentUpdateStructDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [];

        $serviceMock->expects(self::once())->method('newContentUpdateStruct')->with(...$parameters)->willReturn($this->createMock(ContentUpdateStruct::class));

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

        $argument = [$this->createMock(ContentInfo::class)];

        $serviceMock
            ->expects(self::once())
            ->method('loadVersionInfoListByContentInfo')
            ->with($argument)
            ->willReturn([]);

        $decoratedService->loadVersionInfoListByContentInfo($argument);
    }
}
