<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\RoleService;

use Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\RoleService::copyRole
 */
final class CopyRoleTest extends RepositoryTestCase
{
    private const TEXT_LINE_FIELD_TYPE_IDENTIFIER = 'ezstring';

    private const CONTENT_TYPE_IDENTIFIER = 'doomed_content_type';
    private const ROLE_IDENTIFIER = 'role_with_content_type_limitation';
    private const COPIED_ROLE_IDENTIFIER = 'copied_role_with_content_type_limitation';

    private const NONEXISTENT_CONTENT_TYPE_ID = 99999;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCopyRoleWithLimitationValueOfDeletedContentType(): void
    {
        $roleService = $this->getIbexaTestCore()->getRoleService();
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();

        $contentTypeId = $this->createContentType();
        $role = $this->createRoleWithContentTypeLimitation($contentTypeId);

        $contentTypeService->deleteContentType(
            $contentTypeService->loadContentType($contentTypeId)
        );

        $copiedRole = $roleService->copyRole(
            $roleService->loadRoleByIdentifier(self::ROLE_IDENTIFIER),
            $roleService->newRoleCopyStruct(self::COPIED_ROLE_IDENTIFIER)
        );

        self::assertSame(self::COPIED_ROLE_IDENTIFIER, $copiedRole->identifier);
        self::assertNotSame($role->id, $copiedRole->id);
        self::assertSame(
            [$contentTypeId],
            $this->getContentTypeLimitationValues($copiedRole)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testCopyRoleValidatesPoliciesAddedToRoleCopyStruct(): void
    {
        $roleService = $this->getIbexaTestCore()->getRoleService();

        $contentTypeId = $this->createContentType();
        $this->createRoleWithContentTypeLimitation($contentTypeId);

        $policyCreateStruct = $roleService->newPolicyCreateStruct('content', 'edit');
        $policyCreateStruct->addLimitation(
            new ContentTypeLimitation(['limitationValues' => [self::NONEXISTENT_CONTENT_TYPE_ID]])
        );

        $roleCopyStruct = $roleService->newRoleCopyStruct(self::COPIED_ROLE_IDENTIFIER);
        $roleCopyStruct->addPolicy($policyCreateStruct);

        try {
            $roleService->copyRole(
                $roleService->loadRoleByIdentifier(self::ROLE_IDENTIFIER),
                $roleCopyStruct
            );
            self::fail(sprintf('Expected %s to be thrown', LimitationValidationException::class));
        } catch (LimitationValidationException $e) {
            self::assertNotEmpty($e->getLimitationErrors());
        }
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function createContentType(): int
    {
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();

        $typeCreate = $contentTypeService->newContentTypeCreateStruct(self::CONTENT_TYPE_IDENTIFIER);
        $typeCreate->mainLanguageCode = 'eng-GB';
        $typeCreate->nameSchema = '<title>';
        $typeCreate->urlAliasSchema = '<title>';
        $typeCreate->names = ['eng-GB' => 'Doomed content type'];

        $fieldDefinitionCreate = $contentTypeService->newFieldDefinitionCreateStruct(
            'title',
            self::TEXT_LINE_FIELD_TYPE_IDENTIFIER
        );
        $fieldDefinitionCreate->position = 1;
        $fieldDefinitionCreate->names = ['eng-GB' => 'Title'];
        $typeCreate->addFieldDefinition($fieldDefinitionCreate);

        $contentTypeDraft = $contentTypeService->createContentType(
            $typeCreate,
            [$contentTypeService->loadContentTypeGroupByIdentifier('Content')]
        );
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $contentTypeService->loadContentTypeByIdentifier(self::CONTENT_TYPE_IDENTIFIER)->id;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function createRoleWithContentTypeLimitation(int $contentTypeId): Role
    {
        $roleService = $this->getIbexaTestCore()->getRoleService();

        $policyCreateStruct = $roleService->newPolicyCreateStruct('content', 'read');
        $policyCreateStruct->addLimitation(
            new ContentTypeLimitation(['limitationValues' => [$contentTypeId]])
        );

        $roleCreateStruct = $roleService->newRoleCreateStruct(self::ROLE_IDENTIFIER);
        $roleCreateStruct->addPolicy($policyCreateStruct);

        $roleService->publishRoleDraft($roleService->createRole($roleCreateStruct));

        return $roleService->loadRoleByIdentifier(self::ROLE_IDENTIFIER);
    }

    /**
     * @return array<int>
     */
    private function getContentTypeLimitationValues(Role $role): array
    {
        foreach ($role->getPolicies() as $policy) {
            foreach ($policy->getLimitations() as $limitation) {
                if ($limitation instanceof ContentTypeLimitation) {
                    return array_map('intval', $limitation->limitationValues);
                }
            }
        }

        self::fail(
            sprintf(
                'Role "%s" carries no %s',
                $role->identifier,
                ContentTypeLimitation::class
            )
        );
    }
}
