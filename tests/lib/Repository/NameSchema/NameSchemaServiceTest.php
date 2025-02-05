<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\NameSchema;

use Ibexa\Contracts\Core\Event\NameSchema\AbstractSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveContentNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveUrlAliasSchemaEvent;
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
use Traversable;

/**
 * @covers \Ibexa\Core\Repository\NameSchema\NameSchemaService
 */
final class NameSchemaServiceTest extends BaseServiceMockTest
{
    private const NAME_SCHEMA = '<name_schema>';

    public function testResolveUrlAliasSchema(): void
    {
        $content = $this->buildTestContentObject();
        $contentType = $this->buildTestContentTypeStub();

        $event = new ResolveUrlAliasSchemaEvent(['field' => ['<url_alias_schema>']], $content);
        $event->setTokenValues(['eng-GB' => ['url_alias_schema' => 'foo']]);

        $nameSchemaService = $this->buildNameSchemaService($event);

        $result = $nameSchemaService->resolveUrlAliasSchema($content, $contentType);

        self::assertEquals(['eng-GB' => 'foo'], $result);
    }

    public function testResolveUrlAliasSchemaFallbackToNameSchema(): void
    {
        $content = $this->buildTestContentObject();
        $contentType = $this->buildTestContentTypeStub(self::NAME_SCHEMA, '');

        $event = new ResolveUrlAliasSchemaEvent(['field' => [self::NAME_SCHEMA]], $content);
        $event->setTokenValues(['eng-GB' => ['name_schema' => 'bar']]);

        $nameSchemaService = $this->buildNameSchemaService($event);
        $result = $nameSchemaService->resolveUrlAliasSchema($content, $contentType);

        self::assertEquals(['eng-GB' => 'bar'], $result);
    }

    /**
     * @return iterable<string, array{
     *  0: array<int|string, array<string, \Ibexa\Contracts\Core\FieldType\Value>>,
     *  1: array<string, array<string, string>>,
     *  2: array<string>,
     *  3: array<int, array<string, string>>
     * }>
     */
    public static function getDataForTestResolveNameSchema(): iterable
    {
        yield 'Default: Field Map and Languages taken from Content Version' => [
            [],
            [
                'eng-GB' => ['text1' => 'one', 'text2' => 'two'],
                'cro-HR' => ['text1' => 'jedan', 'text2' => 'dva'],
            ],
            [],
            [
                [
                    'eng-GB' => 'one (text)',
                    'cro-HR' => 'jedan (text)',
                ],
                [
                    'eng-GB' => 'one - two',
                    'cro-HR' => 'jedan - dva',
                ],
                [
                    'eng-GB' => 'one - two (two) two (text2)',
                    'cro-HR' => 'jedan - dva (dva) dva (text2)',
                ],
                [
                    'eng-GB' => 'one - two (EZMETAGROUP_0) two',
                    'cro-HR' => 'jedan - dva (EZMETAGROUP_0) dva',
                ],
            ],
        ];

        yield 'Field Map and Languages for update' => [
            [
                'text1' => ['cro-HR' => new TextLineValue('jedan'), 'eng-GB' => new TextLineValue('one')],
                'text2' => ['cro-HR' => new TextLineValue('Dva'), 'eng-GB' => new TextLineValue('two')],
                'text3' => ['eng-GB' => new TextLineValue('three')],
            ],
            [
                'eng-GB' => ['text2' => 'two', 'text3' => 'three'],
                'cro-HR' => ['text2' => 'Dva'],
            ],
            ['eng-GB', 'cro-HR'],
            [
                [
                    'eng-GB' => ' (text)',
                    'cro-HR' => ' (text)',
                ],
                [
                    'eng-GB' => 'three',
                    'cro-HR' => ' - Dva',
                ],
                [
                    'eng-GB' => 'three (two) two (text2)',
                    'cro-HR' => ' - Dva (Dva) Dva (text2)',
                ],
                //known incorrect behavior - using the same group that was in two different statements (<text1> - <text2>)
                [
                    'eng-GB' => 'three (EZMETAGROUP_0) two',
                    'cro-HR' => ' - Dva (EZMETAGROUP_0) Dva',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getDataForTestResolveNameSchema
     *
     * @param array<int|string, array<string, \Ibexa\Contracts\Core\FieldType\Value>> $fieldMap
     * @param array<string, array<string, string>> $tokenValues
     * @param array<string> $languageCodes
     * @param array<int, array<string, string>> $expectedNames
     */
    public function testResolveNameSchema(
        array $fieldMap,
        array $tokenValues,
        array $languageCodes,
        array $expectedNames
    ): void {
        $content = $this->buildTestContentObject();
        $schemas = [
            '<text1> (text)',
            '<text3|(<text1> - <text2>)>',
            '<text3|(<text1> - <text2>)> (<text2>) <text2> (text2)',
            '<text3|(<text1> - <text2>)> (<text1> - <text2>) <text2>',
        ];

        foreach ($schemas as $index => $nameSchema) {
            $contentType = $this->buildTestContentTypeStub($nameSchema, $nameSchema);
            $event = new ResolveContentNameSchemaEvent(
                $content,
                ['field' => ['text3', 'text2', 'text1']],
                $contentType,
                $fieldMap,
                $languageCodes
            );
            $event->setTokenValues($tokenValues);

            $nameSchemaService = $this->buildNameSchemaService($event);

            $result = $nameSchemaService->resolveContentNameSchema(
                $content,
                $fieldMap,
                $languageCodes,
                $contentType
            );

            self::assertEquals(
                $expectedNames[$index],
                $result
            );
        }
    }

    /**
     * Data provider for the testResolve method.
     *
     * @return array<array{
     *  0: array<string, array<string>>,
     *  1: string,
     *  2:  array<int|string, array<string, \Ibexa\Contracts\Core\FieldType\Value>>,
     *  3: array<string, string>,
     *  4: array<string, array<string, string>>,
     *  5?: array{limit?: int, sequence?: string}
     * }>
     *
     * @see testResolve
     */
    public static function getDataForTestResolve(): array
    {
        return [
            [
                ['field' => ['text1']],
                '<text1>',
                [
                    'text1' => ['cro-HR' => new TextLineValue('jedan'), 'eng-GB' => new TextLineValue('one')],
                    'text2' => ['cro-HR' => new TextLineValue('Dva'), 'eng-GB' => new TextLineValue('two')],
                    'text3' => ['eng-GB' => new TextLineValue('three')],
                ],
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
                ['field' => ['text2']],
                '<text2>',
                [
                    'text1' => ['cro-HR' => new TextLineValue('jedan'), 'eng-GB' => new TextLineValue('one')],
                    'text2' => ['cro-HR' => new TextLineValue('Dva'), 'eng-GB' => new TextLineValue('two')],
                    'text3' => ['eng-GB' => new TextLineValue('three')],
                ],
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
                ['field' => ['text2', 'text2']],
                'Hello, <text1> and <text2> and then goodbye and hello again',
                [
                    'text1' => ['cro-HR' => new TextLineValue('jedan'), 'eng-GB' => new TextLineValue('one')],
                    'text2' => ['cro-HR' => new TextLineValue('Dva'), 'eng-GB' => new TextLineValue('two')],
                    'text3' => ['eng-GB' => new TextLineValue('three')],
                ],
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
     * @param array<string, array<string>> $schemaIdentifiers
     * @param array<string> $languageFieldValues field value translations
     * @param array<int|string, array<string, \Ibexa\Contracts\Core\FieldType\Value>> $fieldMap
     * @param array<string, array<string, string>> $fieldTitles [language => [field_identifier => title]]
     * @param array{limit?: int, sequence?: string} $settings NameSchemaService settings
     */
    public function testResolve(
        array $schemaIdentifiers,
        string $nameSchema,
        array $fieldMap,
        array $languageFieldValues,
        array $fieldTitles,
        array $settings = []
    ): void {
        $content = $this->buildTestContentObject();
        $contentType = $this->buildTestContentTypeStub($nameSchema, $nameSchema);

        $event = new ResolveNameSchemaEvent(
            $schemaIdentifiers,
            $contentType,
            $fieldMap,
            $content->versionInfo->languageCodes
        );

        $event->setTokenValues($fieldTitles);

        $nameSchemaService = $this->buildNameSchemaService(
            $event,
            $settings
        );

        $result = $nameSchemaService->resolveNameSchema(
            $nameSchema,
            $contentType,
            $fieldMap,
            $content->versionInfo->languageCodes
        );

        self::assertEquals($languageFieldValues, $result);
    }

    /**
     * @return \Traversable<\Ibexa\Contracts\Core\Repository\Values\Content\Field>
     */
    protected function getFields(): Traversable
    {
        $translatedFieldValueMap = [
            'eng-GB' => [
                'text1' => 'one',
                'text2' => 'two',
                'text3' => '',
            ],
            'cro-HR' => [
                'text1' => 'jedan',
                'text2' => 'dva',
                'text3' => '',
            ],
        ];

        foreach ($translatedFieldValueMap as $languageCode => $fieldValues) {
            foreach ($fieldValues as $fieldDefinitionIdentifier => $textValue) {
                yield new Field(
                    [
                        'languageCode' => $languageCode,
                        'fieldDefIdentifier' => $fieldDefinitionIdentifier,
                        'value' => new TextLineValue($textValue),
                        'fieldTypeIdentifier' => 'ezstring',
                    ]
                );
            }
        }
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
                'internalFields' => iterator_to_array($this->getFields()),
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

    protected function getEventDispatcherMock(
        AbstractSchemaEvent $event
    ): EventDispatcherInterface {
        $eventDispatcherMock = $this->getEventDispatcher();
        $eventDispatcherMock->method('dispatch')
            ->willReturn($event);

        return $eventDispatcherMock;
    }

    /**
     * @param array{limit?: integer, sequence?: string} $settings
     */
    private function buildNameSchemaService(
        AbstractSchemaEvent $event,
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
            $this->getEventDispatcherMock($event),
            $settings
        );
    }
}
