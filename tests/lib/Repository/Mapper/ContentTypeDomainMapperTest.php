<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Mapper;

use Ibexa\Contracts\Core\FieldType\FieldType as FieldTypeInterface;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Language as SPILanguage;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as SPILanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type as SPIContentType;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition as SPIFieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPITypeHandler;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition as APIFieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionUpdateStruct;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\Repository\Mapper\ContentTypeDomainMapper;
use Ibexa\Core\Repository\ProxyFactory\ProxyDomainMapperInterface;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Repository\Mapper\ContentTypeDomainMapper
 */
final class ContentTypeDomainMapperTest extends TestCase
{
    private ContentTypeDomainMapper $mapper;

    private FieldTypeRegistry & MockObject $fieldTypeRegistry;

    protected function setUp(): void
    {
        $this->fieldTypeRegistry = $this->createMock(FieldTypeRegistry::class);

        $this->mapper = new ContentTypeDomainMapper(
            self::createStub(SPITypeHandler::class),
            self::createStub(SPILanguageHandler::class),
            $this->fieldTypeRegistry,
        );
    }

    /**
     * @dataProvider provideFieldDefinitionsInPersistenceOrder
     *
     * @param list<array{string, int}> $spiFieldDefinitions identifier and position pairs in persistence order
     * @param list<string> $expectedIdentifiers
     */
    public function testBuildContentTypeDomainObjectSortsFieldDefinitionsByPositionThenIdentifier(
        array $spiFieldDefinitions,
        array $expectedIdentifiers
    ): void {
        $languageHandler = self::createStub(SPILanguageHandler::class);
        $languageHandler->method('load')->willReturn(new SPILanguage(['id' => 2, 'languageCode' => 'eng-GB']));
        $fieldTypeRegistry = self::createStub(FieldTypeRegistry::class);
        $fieldTypeRegistry->method('getFieldType')->willReturn(self::createStub(FieldTypeInterface::class));
        $mapper = new ContentTypeDomainMapper(
            self::createStub(SPITypeHandler::class),
            $languageHandler,
            $fieldTypeRegistry,
            self::createStub(ProxyDomainMapperInterface::class),
        );

        $contentType = $mapper->buildContentTypeDomainObject(new SPIContentType([
            'id' => 1,
            'status' => SPIContentType::STATUS_DEFINED,
            'identifier' => 'article',
            'name' => ['eng-GB' => 'Article'],
            'created' => 0,
            'modified' => 0,
            'creatorId' => 14,
            'modifierId' => 14,
            'remoteId' => 'article',
            'nameSchema' => '<title>',
            'isContainer' => false,
            'initialLanguageId' => 2,
            'fieldDefinitions' => array_map(
                static fn (int $id, array $fieldDefinition): SPIFieldDefinition => new SPIFieldDefinition([
                    'id' => $id,
                    'identifier' => $fieldDefinition[0],
                    'position' => $fieldDefinition[1],
                    'fieldType' => 'ibexa_string',
                    'fieldGroup' => 'content',
                    'name' => [],
                    'isTranslatable' => false,
                    'isRequired' => false,
                    'isThumbnail' => false,
                    'isInfoCollector' => false,
                    'isSearchable' => false,
                    'defaultValue' => new FieldValue(),
                ]),
                array_keys($spiFieldDefinitions),
                $spiFieldDefinitions,
            ),
        ]));

        self::assertSame(
            $expectedIdentifiers,
            $contentType->getFieldDefinitions()->map(
                static fn (APIFieldDefinition $fieldDefinition): string => $fieldDefinition->getIdentifier(),
            ),
        );
    }

    /**
     * @return iterable<string, array{list<array{string, int}>, list<string>}>
     */
    public static function provideFieldDefinitionsInPersistenceOrder(): iterable
    {
        yield 'no field definitions' => [[], []];
        yield 'already sorted' => [[['title', 1], ['body', 2]], ['title', 'body']];
        yield 'positions out of order' => [
            [['image', 30], ['title', 10], ['body', 20]],
            ['title', 'body', 'image'],
        ];
        yield 'equal positions sorted by identifier' => [
            [['title', 1], ['summary', 0], ['body', 1], ['author', 1]],
            ['summary', 'author', 'body', 'title'],
        ];
        yield 'zero and negative positions first' => [
            [['title', 1], ['zero', 0], ['minus', -1]],
            ['minus', 'zero', 'title'],
        ];
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

    private function configureFieldTypeRegistry(
        TextLineValue $expectedInput,
        FieldValue $persistedValue
    ): void {
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

        $this->fieldTypeRegistry->method('getFieldType')->with('ibexa_string')->willReturn($fieldType);
    }
}
