<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository;

use Ibexa\Contracts\Core\FieldType\FieldType as SPIFieldType;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler;
use Ibexa\Contracts\Core\Persistence\User\Handler as UserHandler;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository as RepositoryInterface;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\Repository\ContentTypeService;
use Ibexa\Core\Repository\Mapper\ContentDomainMapper;
use Ibexa\Core\Repository\Mapper\ContentTypeDomainMapper;
use PHPUnit\Framework\TestCase;

final class ContentTypeServiceValidationErrorTest extends TestCase
{
    public function testValidateFieldDefinitionCreateStructBuildsTranslatableSearchableError(): void
    {
        $service = new class(
            $this->createMock(RepositoryInterface::class),
            $this->createMock(Handler::class),
            $this->createMock(UserHandler::class),
            $this->createMock(ContentDomainMapper::class),
            $this->createMock(ContentTypeDomainMapper::class),
            $this->createMock(FieldTypeRegistry::class),
            $this->createMock(PermissionResolver::class)
        ) extends ContentTypeService {
            /**
             * @return array<\Ibexa\Contracts\Core\FieldType\ValidationError>
             */
            public function exposeValidateFieldDefinitionCreateStruct(
                FieldDefinitionCreateStruct $fieldDefinitionCreateStruct,
                SPIFieldType $fieldType
            ): array {
                return $this->validateFieldDefinitionCreateStruct($fieldDefinitionCreateStruct, $fieldType);
            }
        };

        $fieldDefinitionCreateStruct = new FieldDefinitionCreateStruct();
        $fieldDefinitionCreateStruct->fieldTypeIdentifier = 'ibexa_non_searchable';
        $fieldDefinitionCreateStruct->isSearchable = true;
        $fieldDefinitionCreateStruct->validatorConfiguration = [];
        $fieldDefinitionCreateStruct->fieldSettings = [];

        $fieldType = $this->createMock(SPIFieldType::class);
        $fieldType->method('isSearchable')->willReturn(false);
        $fieldType->method('validateValidatorConfiguration')->willReturn([]);
        $fieldType->method('validateFieldSettings')->willReturn([]);

        $validationErrors = $service->exposeValidateFieldDefinitionCreateStruct($fieldDefinitionCreateStruct, $fieldType);

        self::assertCount(1, $validationErrors);
        self::assertInstanceOf(Message::class, $validationErrors[0]->getTranslatableMessage());
        self::assertSame(
            'Field type ibexa_non_searchable is not searchable',
            (string) $validationErrors[0]->getTranslatableMessage()
        );
    }
}
