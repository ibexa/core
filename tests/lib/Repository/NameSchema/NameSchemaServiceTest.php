<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\NameSchema;

use Ibexa\Contracts\Core\Event\ResolveUrlAliasSchemaEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCollection as APIFieldDefinitionCollection;
use Ibexa\Core\FieldType\TextLine\Type as TextLineFieldType;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\Repository\NameSchema\NameSchemaService;
use Ibexa\Core\Repository\NameSchema\SchemaIdentifierExtractor;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinitionCollection;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Ibexa\Core\Repository\NameSchema\NameSchemaService
 */
class NameSchemaServiceTest extends BaseServiceMockTest
{
    private const NAME_SCHEMA = '<name_schema>';

    public function testResolveUrlAliasSchema(): void
    {
        $content = $this->buildTestContentObject();
        $contentType = $this->buildTestContentTypeStub();

        $nameSchemaService = $this->buildNameSchemaService(
            ['field' => ['<url_alias_schema>']],
            $content,
            ['eng-GB' => ['url_alias_schema' => 'foo']]
        );

        $result = $nameSchemaService->resolveUrlAliasSchema($content, $contentType);

        self::assertEquals(['eng-GB' => 'foo'], $result);
    }

    public function testResolveUrlAliasSchemaFallbackToNameSchema(): void
    {
        $content = $this->buildTestContentObject();
        $contentType = $this->buildTestContentTypeStub(self::NAME_SCHEMA, '');

        $nameSchemaService = $this->buildNameSchemaService(
            ['field' => [self::NAME_SCHEMA]],
            $content,
            ['eng-GB' => ['name_schema' => 'bar']]
        );

        $result = $nameSchemaService->resolveUrlAliasSchema($content, $contentType);

        self::assertEquals(['eng-GB' => 'bar'], $result);
    }

    /**
     * @return iterable<array<string, array<string, string>>, array<string>, array<string, string>>
     */
    public static function getDataForTestResolveNameSchema(): iterable
    {
        yield 'Default: Field Map and Languages taken from Content Version' => [
            [],
            [],
            [
                'eng-GB' => 'two',
                'cro-HR' => 'dva',
            ],
        ];

        yield 'Field Map and Languages for update' => [
            [
                'text1' => ['cro-HR' => new TextLineValue('jedan'), 'eng-GB' => new TextLineValue('one')],
                'text2' => ['cro-HR' => new TextLineValue('Dva'), 'eng-GB' => new TextLineValue('two')],
                'text3' => ['eng-GB' => new TextLineValue('three')],
            ],
            ['eng-GB', 'cro-HR'],
            [
                'eng-GB' => 'three',
                'cro-HR' => 'Dva',
            ],
        ];
    }

    /**
     * @dataProvider getDataForTestResolveNameSchema
     *
     * @param array<string, array<string, string>> $fieldMap A map of Field Definition Identifier and Language Code to Field Value
     * @param array<string> $languageCodes
     * @param array<string, string> $expectedNames
     */
    public function testResolveNameSchema(array $fieldMap, array $languageCodes, array $expectedNames): void
    {
        $content = $this->buildTestContentObject();
        $nameSchema = '<text3|text2>';
        $nameSchemaService = $this->buildNameSchemaService(
            ['field' => [$nameSchema]],
            $content,
            []
        );
        $contentType = $this->buildTestContentTypeStub($nameSchema, $nameSchema);

        $result = $nameSchemaService->resolveNameSchema($content, $fieldMap, $languageCodes, $contentType);

        self::assertEquals(
            $expectedNames,
            $result
        );
    }

    /**
     * Data provider for the testResolve method.
     *
     * @see testResolve
     */
    public static function getDataForTestResolve(): array
    {
        return [
            [
                ['text1'],
                '<text1>',
                [
                    'eng-GB' => 'one',
                    'cro-HR' => 'jedan',
                ],
                [
                    'eng-GB' => ['text1' => 'one'],
                    'cro-HR' => ['text1' => 'jedan'],
                ],
            ],
            [
                ['text2'],
                '<text2>',
                [
                    'eng-GB' => 'two',
                    'cro-HR' => 'dva',
                ],
                [
                    'eng-GB' => ['text2' => 'two'],
                    'cro-HR' => ['text2' => 'dva'],
                ],
            ],
            [
                ['text1', 'text2'],
                'Hello, <text1> and <text2> and then goodbye and hello again',
                [
                    'eng-GB' => 'Hello, one and two and then goodbye...',
                    'cro-HR' => 'Hello, jedan and dva and then goodb...',
                ],
                [
                    'eng-GB' => ['text1' => 'one', 'text2' => 'two'],
                    'cro-HR' => ['text1' => 'jedan', 'text2' => 'dva'],
                ],
                [
                    'limit' => 38,
                    'sequence' => '...',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getDataForTestResolve
     *
     * @param string[] $schemaIdentifiers
     * @param string[] $languageFieldValues field value translations
     * @param string[] $fieldTitles [language => [field_identifier => title]]
     * @param array $settings NameSchemaService settings
     */
    public function testResolve(
        array $schemaIdentifiers,
        string $nameSchema,
        array $languageFieldValues,
        array $fieldTitles,
        array $settings = []
    ): void {
        $content = $this->buildTestContentObject();
        $nameSchemaService = $this->buildNameSchemaService(
            ['field' => [$nameSchema]],
            $content,
            [],
            $settings
        );
        $contentType = $this->buildTestContentTypeStub($nameSchema, $nameSchema);

        $result = $nameSchemaService->resolve(
            $nameSchema,
            $contentType,
            $content->fields,
            $content->versionInfo->languageCodes
        );

        self::assertEquals($languageFieldValues, $result);
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Field[]
     */
    protected function getFields(): array
    {
        return [
            new Field(
                [
                    'languageCode' => 'eng-GB',
                    'fieldDefIdentifier' => 'text1',
                    'value' => new TextLineValue('one'),
                ]
            ),
            new Field(
                [
                    'languageCode' => 'eng-GB',
                    'fieldDefIdentifier' => 'text2',
                    'value' => new TextLineValue('two'),
                ]
            ),
            new Field(
                [
                    'languageCode' => 'eng-GB',
                    'fieldDefIdentifier' => 'text3',
                    'value' => new TextLineValue(''),
                ]
            ),
            new Field(
                [
                    'languageCode' => 'cro-HR',
                    'fieldDefIdentifier' => 'text1',
                    'value' => new TextLineValue('jedan'),
                ]
            ),
            new Field(
                [
                    'languageCode' => 'cro-HR',
                    'fieldDefIdentifier' => 'text2',
                    'value' => new TextLineValue('dva'),
                ]
            ),
            new Field(
                [
                    'languageCode' => 'cro-HR',
                    'fieldDefIdentifier' => 'text3',
                    'value' => new TextLineValue(''),
                ]
            ),
        ];
    }

    protected function getFieldDefinitions(): APIFieldDefinitionCollection
    {
        return new FieldDefinitionCollection(
            [
                new FieldDefinition(
                    [
                        'id' => '1',
                        'identifier' => 'text1',
                        'fieldTypeIdentifier' => 'ezstring',
                    ]
                ),
                new FieldDefinition(
                    [
                        'id' => '2',
                        'identifier' => 'text2',
                        'fieldTypeIdentifier' => 'ezstring',
                    ]
                ),
                new FieldDefinition(
                    [
                        'id' => '3',
                        'identifier' => 'text3',
                        'fieldTypeIdentifier' => 'ezstring',
                    ]
                ),
            ]
        );
    }

    /**
     * Build Content Object stub for testing purpose.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    protected function buildTestContentObject()
    {
        return new Content(
            [
                'internalFields' => $this->getFields(),
                'versionInfo' => new VersionInfo(
                    [
                        'languageCodes' => ['eng-GB', 'cro-HR'],
                    ]
                ),
            ]
        );
    }

    protected function buildTestContentTypeStub(
        string $nameSchema = '<name_schema>',
        string $urlAliasSchema = '<url_alias_schema>'
    ): ContentType {
        return new ContentType(
            [
                'nameSchema' => $nameSchema,
                'urlAliasSchema' => $urlAliasSchema,
                'fieldDefinitions' => $this->getFieldDefinitions(),
            ]
        );
    }

    /**
     * @param array<string, array<string, string>> $schemaIdentifiers
     */
    protected function getEventDispatcherMock(
        array $schemaIdentifiers,
        Content $content,
        array $tokenValues
    ): EventDispatcherInterface {
        $event = new ResolveUrlAliasSchemaEvent($schemaIdentifiers, $content);
        $event->setTokenValues($tokenValues);

        $eventDispatcherMock = $this->getEventDispatcher();
        $eventDispatcherMock->method('dispatch')
            ->willReturn($event);

        return $eventDispatcherMock;
    }

    /**
     * @param array<string, array<string>> $schemaIdentifiers
     * @param array<string, array<string, string>> $tokenValues
     * @param array{limit?: integer, sequence?: string} $settings
     */
    private function buildNameSchemaService(
        array $schemaIdentifiers,
        Content $content,
        array $tokenValues,
        array $settings = []
    ): NameSchemaService {
        $fieldTypeRegistryMock = $this->getFieldTypeRegistryMock();
        $fieldTypeRegistryMock
            ->method('getFieldType')
            ->with('ezstring')
            ->willReturn(new TextLineFieldType());

        return new NameSchemaService(
            $fieldTypeRegistryMock,
            new SchemaIdentifierExtractor(),
            $this->getEventDispatcherMock($schemaIdentifiers, $content, $tokenValues),
            $settings
        );
    }
}
