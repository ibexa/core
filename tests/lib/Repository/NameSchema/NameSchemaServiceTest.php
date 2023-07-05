<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\NameSchema;

use Ibexa\Contracts\Core\Event\ResolveUrlAliasSchemaEvent;
use Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\Repository\NameSchema\NameSchemaService;
use Ibexa\Core\Repository\NameSchema\SchemaIdentifierExtractor;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Ibexa\Core\Repository\NameSchema\NameSchemaService
 */
class NameSchemaServiceTest extends BaseServiceMockTest
{
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
        $contentType = $this->buildTestContentTypeStub('<name_schema>', '');

        $nameSchemaService = $this->buildNameSchemaService(
            ['field' => ['<name_schema>']],
            $content,
            ['eng-GB' => ['name_schema' => 'bar']]
        );

        $result = $nameSchemaService->resolveUrlAliasSchema($content, $contentType);

        self::assertEquals(['eng-GB' => 'bar'], $result);
    }

    public function testResolveNameSchema()
    {
        $serviceMock = $this->getPartlyMockedNameSchemaService(['resolve']);

        $content = $this->buildTestContentObject();
        $contentType = $this->buildTestContentTypeStub();

        $serviceMock->expects(
            $this->once()
        )->method(
            'resolve'
        )->with(
            '<name_schema>',
            $this->equalTo($contentType),
            $this->equalTo($content->fields),
            $this->equalTo($content->versionInfo->languageCodes)
        )->will(
            $this->returnValue([42])
        );

        $result = $serviceMock->resolveNameSchema($content, [], [], $contentType);

        self::assertEquals([42], $result);
    }

    public function testResolveNameSchemaWithFields()
    {
        $serviceMock = $this->getPartlyMockedNameSchemaService(['resolve']);

        $content = $this->buildTestContentObject();
        $contentType = $this->buildTestContentTypeStub();

        $fields = [];
        $fields['text3']['cro-HR'] = new TextLineValue('tri');
        $fields['text1']['ger-DE'] = new TextLineValue('ein');
        $fields['text2']['ger-DE'] = new TextLineValue('zwei');
        $fields['text3']['ger-DE'] = new TextLineValue('drei');
        $mergedFields = $fields;
        $mergedFields['text1']['cro-HR'] = new TextLineValue('jedan');
        $mergedFields['text2']['cro-HR'] = new TextLineValue('dva');
        $mergedFields['text1']['eng-GB'] = new TextLineValue('one');
        $mergedFields['text2']['eng-GB'] = new TextLineValue('two');
        $mergedFields['text3']['eng-GB'] = new TextLineValue('');
        $languages = ['eng-GB', 'cro-HR', 'ger-DE'];

        $serviceMock->expects(
            $this->once()
        )->method(
            'resolve'
        )->with(
            '<name_schema>',
            $this->equalTo($contentType),
            $this->equalTo($mergedFields),
            $this->equalTo($languages)
        )->will(
            $this->returnValue([42])
        );

        $result = $serviceMock->resolveNameSchema($content, $fields, $languages, $contentType);

        self::assertEquals([42], $result);
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param string[] $schemaIdentifiers
     * @param string $nameSchema
     * @param string[] $languageFieldValues field value translations
     * @param string[] $fieldTitles [language => [field_identifier => title]]
     * @param array $settings NameSchemaService settings
     */
    public function testResolve(
        array $schemaIdentifiers,
        $nameSchema,
        $languageFieldValues,
        $fieldTitles,
        $settings = []
    ) {
        $serviceMock = $this->getPartlyMockedNameSchemaService(['getFieldTitles'], $settings);

        $content = $this->buildTestContentObject();
        $contentType = $this->buildTestContentTypeStub();

        $index = 0;
        foreach ($languageFieldValues as $languageCode => $fieldValue) {
            $serviceMock->expects(
                $this->at($index++)
            )->method(
                'getFieldTitles'
            )->with(
                $schemaIdentifiers,
                $contentType,
                $content->fields,
                $languageCode
            )->will(
                $this->returnValue($fieldTitles[$languageCode])
            );
        }

        $result = $serviceMock->resolve($nameSchema, $contentType, $content->fields, $content->versionInfo->languageCodes);

        self::assertEquals($languageFieldValues, $result);
    }

    /**
     * Data provider for the @return array.
     *
     * @see testResolve method.
     */
    public function resolveDataProvider()
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
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Field[]
     */
    protected function getFields()
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

    /**
     * @return \Ibexa\Core\Repository\Values\ContentType\FieldDefinition[]
     */
    protected function getFieldDefinitions()
    {
        return [
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
        ];
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
     * Returns the content service to test with $methods mocked.
     *
     * Injected Repository comes from {@see getRepositoryMock()}
     *
     * @param string[] $methods
     * @param array $settings
     *
     * @return \Ibexa\Contracts\Core\Repository\NameSchema\NameSchemaServiceInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPartlyMockedNameSchemaService(array $methods = null, array $settings = []): NameSchemaServiceInterface
    {
        return $this->getMockBuilder(NameSchemaService::class)
            ->setMethods($methods)
            ->setConstructorArgs(
                [
                    $this->getFieldTypeRegistryMock(),
                    new SchemaIdentifierExtractor(),
                    $this->getEventDispatcher(),
                    $settings,
                ]
            )
            ->getMock();
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
     */
    private function buildNameSchemaService(
        array $schemaIdentifiers,
        Content $content,
        array $tokenValues
    ): NameSchemaService {
        return new NameSchemaService(
            $this->getFieldTypeRegistryMock(),
            new SchemaIdentifierExtractor(),
            $this->getEventDispatcherMock($schemaIdentifiers, $content, $tokenValues),
        );
    }
}
