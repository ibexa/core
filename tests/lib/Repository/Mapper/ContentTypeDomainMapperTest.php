<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Mapper;

use Ibexa\Contracts\Core\FieldType\FieldType as FieldTypeInterface;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as SPILanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPITypeHandler;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionUpdateStruct;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\Repository\Mapper\ContentTypeDomainMapper;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContentTypeDomainMapper::class)]
final class ContentTypeDomainMapperTest extends TestCase
{
    private ContentTypeDomainMapper $mapper;

    private FieldTypeRegistry & Stub $fieldTypeRegistry;

    protected function setUp(): void
    {
        $this->fieldTypeRegistry = self::createStub(FieldTypeRegistry::class);

        $this->mapper = new ContentTypeDomainMapper(
            self::createStub(SPITypeHandler::class),
            self::createStub(SPILanguageHandler::class),
            $this->fieldTypeRegistry,
        );
    }

    public function testBuildSPIFieldDefinitionFromUpdateStructPreservesDefaultValueWhenNotSet(): void
    {
        $existingDefaultValue = new TextLineValue('Foo');
        $persistedValue = new FieldValue(['data' => 'Foo']);

        $this->configureFieldTypeRegistry($existingDefaultValue, $persistedValue);

        $updateStruct = new FieldDefinitionUpdateStruct();
        $updateStruct->position = 100;

        $spiFieldDefinition = $this->mapper->buildSPIFieldDefinitionFromUpdateStruct(
            $updateStruct,
            $this->buildFieldDefinition($existingDefaultValue),
            'eng-GB'
        );

        self::assertSame($persistedValue, $spiFieldDefinition->defaultValue);
        self::assertSame(100, $spiFieldDefinition->position);
    }

    #[DataProvider('provideExplicitDefaultValues')]
    public function testBuildSPIFieldDefinitionFromUpdateStructOverridesDefaultValueWhenExplicitlySet(
        TextLineValue $newDefaultValue,
        FieldValue $persistedValue
    ): void {
        $this->configureFieldTypeRegistry($newDefaultValue, $persistedValue);

        $updateStruct = new FieldDefinitionUpdateStruct();
        $updateStruct->defaultValue = $newDefaultValue;

        $spiFieldDefinition = $this->mapper->buildSPIFieldDefinitionFromUpdateStruct(
            $updateStruct,
            $this->buildFieldDefinition(new TextLineValue('Foo')),
            'eng-GB'
        );

        self::assertSame($persistedValue, $spiFieldDefinition->defaultValue);
    }

    /**
     * @return iterable<string, array{TextLineValue, FieldValue}>
     */
    public static function provideExplicitDefaultValues(): iterable
    {
        yield 'new non-empty value overrides the existing default value' => [
            new TextLineValue('Bar'),
            new FieldValue(['data' => 'Bar']),
        ];

        yield 'empty value clears the existing default value' => [
            new TextLineValue(''),
            new FieldValue(['data' => '']),
        ];
    }

    private function buildFieldDefinition(TextLineValue $defaultValue): FieldDefinition
    {
        return new FieldDefinition([
            'id' => 1,
            'identifier' => 'my_name',
            'fieldTypeIdentifier' => 'ibexa_string',
            'fieldGroup' => 'content',
            'defaultValue' => $defaultValue,
            'isTranslatable' => false,
            'isRequired' => false,
            'isInfoCollector' => false,
            'isThumbnail' => false,
            'isSearchable' => true,
            'position' => 1,
        ]);
    }

    private function configureFieldTypeRegistry(TextLineValue $expectedInput, FieldValue $persistedValue): void
    {
        $fieldType = $this->createMock(FieldTypeInterface::class);
        $fieldType->method('validateValidatorConfiguration')->willReturn([]);
        $fieldType->method('validateFieldSettings')->willReturn([]);
        $fieldType->method('isSearchable')->willReturn(true);
        $fieldType
            ->expects(self::once())
            ->method('acceptValue')
            ->with($expectedInput)
            ->willReturn($expectedInput);
        $fieldType
            ->expects(self::once())
            ->method('toPersistenceValue')
            ->with($expectedInput)
            ->willReturn($persistedValue);

        $this->fieldTypeRegistry->method('getFieldType')->willReturn($fieldType);
    }
}
