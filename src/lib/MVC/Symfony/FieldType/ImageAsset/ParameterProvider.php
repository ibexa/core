<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\FieldType\ImageAsset;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\FieldTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\MVC\Symfony\FieldType\View\ParameterProviderInterface;

class ParameterProvider implements ParameterProviderInterface
{
    private Repository $repository;

    private PermissionResolver $permissionsResolver;

    /** @var \Ibexa\Core\Repository\FieldTypeService */
    private FieldTypeService $fieldTypeService;

    /**
     * @param \Ibexa\Contracts\Core\Repository\Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->permissionsResolver = $repository->getPermissionResolver();
        $this->fieldTypeService = $repository->getFieldTypeService();
    }

    /**
     * {@inheritdoc}
     */
    public function getViewParameters(Field $field): array
    {
        $fieldType = $this->fieldTypeService->getFieldType($field->fieldTypeIdentifier);

        if ($fieldType->isEmptyValue($field->value)) {
            return [
                'available' => null,
            ];
        }

        try {
            $contentInfo = $this->loadContentInfo(
                (int)$field->value->destinationContentId
            );

            return [
                'available' => !$contentInfo->isTrashed() && $this->userHasPermissions($contentInfo),
            ];
        } catch (NotFoundException $exception) {
            return [
                'available' => false,
            ];
        }
    }

    /**
     * @param int $id
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function loadContentInfo(int $id): ContentInfo
    {
        return $this->repository->sudo(
            static function (Repository $repository) use ($id): \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo {
                return $repository->getContentService()->loadContentInfo($id);
            }
        );
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     *
     * @return bool
     */
    private function userHasPermissions(ContentInfo $contentInfo): bool
    {
        if ($this->permissionsResolver->canUser('content', 'read', $contentInfo)) {
            return true;
        }

        if ($this->permissionsResolver->canUser('content', 'view_embed', $contentInfo)) {
            return true;
        }

        return false;
    }
}
