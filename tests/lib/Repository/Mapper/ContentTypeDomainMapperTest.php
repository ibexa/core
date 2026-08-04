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
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Mapper\ContentTypeDomainMapper
 */
final class ContentTypeDomainMapperTest extends TestCase
{
    private ContentTypeDomainMapper $mapper;

    /** @var \Ibexa\Core\FieldType\FieldTypeRegistry&\PHPUnit\Framework\MockObject\MockObject */
    private FieldTypeRegistry $fieldTypeRegistry;

    protected function setUp(): void
    {
        $this->fieldTypeRegistry = $this->createMock(FieldTypeRegistry::class);

        $this->mapper = new ContentTypeDomainMapper(
            $this->createMock(SPITypeHandler::class),
            $this->createMock(SPILanguageHandler::class),
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

    /**
     * @dataProvider provideExplicitDefaultValues
     */
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
    public function provideExplicitDefaultValues(): iterable
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
            'fieldTypeIdentifier' => 'ezstring',
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

        $this->fieldTypeRegistry->method('getFieldType')->with('ezstring')->willReturn($fieldType);
    }
}
