<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\FieldType\ImageAsset;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\FieldType;
use Ibexa\Contracts\Core\Repository\FieldTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\ImageAsset\Value as ImageAssetValue;
use Ibexa\Core\MVC\Symfony\FieldType\ImageAsset\ParameterProvider;
use Ibexa\Core\Repository\SiteAccessAware\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ParameterProviderTest extends TestCase
{
    /** @var Repository|MockObject */
    private $repository;

    /** @var PermissionResolver|MockObject */
    private $permissionsResolver;

    /** @var ParameterProvider */
    private $parameterProvider;

    /** @var FieldType|MockObject */
    private $fieldType;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(Repository::class);
        $this->permissionsResolver = $this->createMock(PermissionResolver::class);
        $this->fieldType = $this->createMock(FieldType::class);

        $this->repository
            ->method('getPermissionResolver')
            ->willReturn($this->permissionsResolver);

        $fieldTypeService = $this->createMock(FieldTypeService::class);

        $this->repository
            ->method('getFieldTypeService')
            ->willReturn($fieldTypeService);

        $fieldTypeService
            ->method('getFieldType')
            ->with('ibexa_image_asset')
            ->willReturn($this->fieldType);

        $this->parameterProvider = new ParameterProvider($this->repository);
    }

    public function dataProviderForTestGetViewParameters(): array
    {
        return [
            [ContentInfo::STATUS_PUBLISHED, ['available' => true]],
            [ContentInfo::STATUS_TRASHED, ['available' => false]],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetViewParameters
     */
    public function testGetViewParameters(
        $status,
        array $expected
    ): void {
        $destinationContentId = 1;

        $this->fieldType
            ->method('isEmptyValue')
            ->willReturn(false);

        $closure = static function (Repository $repository) use ($destinationContentId) {
            return $repository->getContentService()->loadContentInfo($destinationContentId);
        };

        $this->repository
            ->method('sudo')
            ->with($closure)
            ->willReturn(new ContentInfo([
                'status' => $status,
            ]));

        $this->permissionsResolver
            ->method('canUser')
            ->willReturn(true);

        $actual = $this->parameterProvider->getViewParameters($this->createField($destinationContentId));

        self::assertEquals($expected, $actual);
    }

    public function testGetViewParametersHandleNotFoundException(): void
    {
        $destinationContentId = 1;

        $this->fieldType
            ->method('isEmptyValue')
            ->willReturn(false);

        $closure = static function (Repository $repository) use ($destinationContentId) {
            return $repository->getContentService()->loadContentInfo($destinationContentId);
        };

        $this->repository
            ->expects(self::once())
            ->method('sudo')
            ->with($closure)
            ->willThrowException($this->createMock(NotFoundException::class));

        $actual = $this->parameterProvider->getViewParameters(
            $this->createField($destinationContentId)
        );

        self::assertEquals([
            'available' => false,
        ], $actual);
    }

    public function testGetViewParametersHandleUnauthorizedAccess(): void
    {
        $destinationContentId = 1;

        $this->fieldType
            ->method('isEmptyValue')
            ->willReturn(false);

        $contentInfo = $this->createMock(ContentInfo::class);

        $this->repository
            ->method('sudo')
            ->willReturn($contentInfo)
        ;

        $this->permissionsResolver
            ->expects(self::at(0))
            ->method('canUser')
            ->with('content', 'read', $contentInfo)
            ->willReturn(false)
        ;

        $this->permissionsResolver
            ->expects(self::at(1))
            ->method('canUser')
            ->with('content', 'view_embed', $contentInfo)
            ->willReturn(false)
        ;

        $actual = $this->parameterProvider->getViewParameters(
            $this->createField($destinationContentId)
        );

        self::assertEquals([
            'available' => false,
        ], $actual);
    }

    public function testGetViewParametersHandleEmptyValue(): void
    {
        $destinationContentId = 1;

        $this->fieldType
            ->method('isEmptyValue')
            ->willReturn(true);

        $contentInfo = $this->createMock(ContentInfo::class);

        $this->repository
            ->method('sudo')
            ->willReturn($contentInfo)
        ;

        $actual = $this->parameterProvider->getViewParameters(
            $this->createField($destinationContentId)
        );

        self::assertEquals([
            'available' => null,
        ], $actual);
    }

    /**
     * @param int $destinationContentId
     *
     * @return Field
     */
    private function createField(int $destinationContentId): Field
    {
        return new Field([
            'value' => new ImageAssetValue($destinationContentId),
            'fieldTypeIdentifier' => 'ibexa_image_asset',
        ]);
    }
}
