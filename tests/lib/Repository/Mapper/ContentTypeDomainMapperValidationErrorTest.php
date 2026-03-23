<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Mapper;

use Ibexa\Contracts\Core\FieldType\FieldType as SPIFieldType;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as SPILanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPITypeHandler;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use Ibexa\Core\Base\Exceptions\ContentTypeFieldDefinitionValidationException;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\Repository\Mapper\ContentTypeDomainMapper;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use PHPUnit\Framework\TestCase;

final class ContentTypeDomainMapperValidationErrorTest extends TestCase
{
    public function testBuildSPIFieldDefinitionFromUpdateStructBuildsTranslatableSearchableError(): void
    {
        $fieldType = $this->createMock(SPIFieldType::class);
        $fieldType->method('isSearchable')->willReturn(false);
        $fieldType->method('validateValidatorConfiguration')->willReturn([]);
        $fieldType->method('validateFieldSettings')->willReturn([]);

        $fieldTypeRegistry = $this->createMock(FieldTypeRegistry::class);
        $fieldTypeRegistry->method('getFieldType')->with('ibexa_non_searchable')->willReturn($fieldType);

        $mapper = new ContentTypeDomainMapper(
            $this->createMock(SPITypeHandler::class),
            $this->createMock(SPILanguageHandler::class),
            $fieldTypeRegistry
        );

        $fieldDefinitionUpdateStruct = new FieldDefinitionUpdateStruct();
        $fieldDefinitionUpdateStruct->isSearchable = true;
        $fieldDefinitionUpdateStruct->validatorConfiguration = [];
        $fieldDefinitionUpdateStruct->fieldSettings = [];

        $fieldDefinition = new FieldDefinition([
            'id' => 1,
            'identifier' => 'test_field',
            'fieldTypeIdentifier' => 'ibexa_non_searchable',
            'fieldGroup' => 'content',
            'position' => 0,
            'isTranslatable' => false,
            'isRequired' => false,
            'isThumbnail' => false,
            'isInfoCollector' => false,
            'isSearchable' => false,
            'mainLanguageCode' => 'eng-GB',
            'names' => ['eng-GB' => 'Test field'],
            'descriptions' => ['eng-GB' => ''],
            'validatorConfiguration' => [],
            'fieldSettings' => [],
            'defaultValue' => null,
            'prioritizedLanguages' => [],
        ]);

        try {
            $mapper->buildSPIFieldDefinitionFromUpdateStruct($fieldDefinitionUpdateStruct, $fieldDefinition, 'eng-GB');
            self::fail('Expected ContentTypeFieldDefinitionValidationException was not thrown.');
        } catch (ContentTypeFieldDefinitionValidationException $exception) {
            $errors = $exception->getFieldErrors();

            self::assertArrayHasKey('test_field', $errors);
            self::assertCount(1, $errors['test_field']);
            self::assertInstanceOf(Message::class, $errors['test_field'][0]->getTranslatableMessage());
            self::assertSame(
                'Field type ibexa_non_searchable is not searchable',
                (string) $errors['test_field'][0]->getTranslatableMessage()
            );
        }
    }
}
