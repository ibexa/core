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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParameterProviderTest extends TestCase
{
    /** @var \Ibexa\Core\Repository\SiteAccessAware\Repository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $permissionsResolver;

    /** @var \Ibexa\Core\MVC\Symfony\FieldType\ImageAsset\ParameterProvider */
    private $parameterProvider;

    /** @var \Ibexa\Contracts\Core\Repository\FieldType|\PHPUnit\Framework\MockObject\MockObject */
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

    public static function dataProviderForTestGetViewParameters(): array
    {
        return [
            [ContentInfo::STATUS_PUBLISHED, ['available' => true]],
            [ContentInfo::STATUS_TRASHED, ['available' => false]],
        ];
    }

    #[DataProvider('dataProviderForTestGetViewParameters')]
    public function testGetViewParameters($status, array $expected): void
    {
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
            ->willThrowException(self::createStub(NotFoundException::class));

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

        $contentInfo = self::createStub(ContentInfo::class);

        $this->repository
            ->method('sudo')
            ->willReturn($contentInfo)
        ;

        $matcher = self::exactly(2);
        $this->permissionsResolver
            ->expects($matcher)
            ->method('canUser')
            ->willReturnCallback(static function (string $module, string $function, object $object, array $targets = []) use ($matcher, $contentInfo): bool {
                self::assertSame('content', $module);
                self::assertSame($contentInfo, $object);
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('read', $function);
                } else {
                    self::assertSame('view_embed', $function);
                }

                return false;
            });

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

        $contentInfo = self::createStub(ContentInfo::class);

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
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Field
     */
    private function createField(int $destinationContentId): Field
    {
        return new Field([
            'value' => new ImageAssetValue($destinationContentId),
            'fieldTypeIdentifier' => 'ibexa_image_asset',
        ]);
    }
}
