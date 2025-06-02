<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content;

use function count;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct as LocationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Relation as SPIRelation;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry as Registry;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\Mapper\ResolveVirtualFieldSubscriber;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\Core\Persistence\Legacy\Content\StorageRegistry;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Mapper
 */
class MapperTest extends LanguageAwareTestCase
{
    /**
     * Value converter registry mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry
     */
    protected $valueConverterRegistryMock;

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\CreateStruct
     */
    protected function getCreateStructFixture()
    {
        $struct = new CreateStruct();

        $struct->name = 'Content name';
        $struct->typeId = 23;
        $struct->sectionId = 42;
        $struct->ownerId = 13;
        $struct->initialLanguageId = 2;
        $struct->locations = [
            new LocationCreateStruct(
                ['parentId' => 2]
            ),
            new LocationCreateStruct(
                ['parentId' => 3]
            ),
            new LocationCreateStruct(
                ['parentId' => 4]
            ),
        ];
        $struct->fields = [new Field()];

        return $struct;
    }

    public function testCreateVersionInfoForContent()
    {
        $content = $this->getFullContentFixture();
        $time = time();

        $mapper = $this->getMapper();

        $versionInfo = $mapper->createVersionInfoForContent(
            $content,
            1,
            14
        );

        $this->assertPropertiesCorrect(
            [
                'id' => null,
                'versionNo' => 1,
                'creatorId' => 14,
                'status' => 0,
                'initialLanguageCode' => 'eng-GB',
                'languageCodes' => ['eng-GB'],
            ],
            $versionInfo
        );
        self::assertGreaterThanOrEqual($time, $versionInfo->creationDate);
        self::assertGreaterThanOrEqual($time, $versionInfo->modificationDate);
    }

    /**
     * Returns a Content fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getFullContentFixture()
    {
        $content = new Content();

        $content->fields = [
            new Field(['languageCode' => 'eng-GB']),
        ];
        $content->versionInfo = new VersionInfo(
            [
                'versionNo' => 1,
                'initialLanguageCode' => 'eng-GB',
                'languageCodes' => ['eng-GB'],
            ]
        );

        $content->versionInfo->contentInfo = new ContentInfo();
        $content->versionInfo->contentInfo->id = 2342;
        $content->versionInfo->contentInfo->contentTypeId = 23;
        $content->versionInfo->contentInfo->sectionId = 42;
        $content->versionInfo->contentInfo->ownerId = 13;

        return $content;
    }

    public function testConvertToStorageValue()
    {
        $convMock = $this->createMock(Converter::class);
        $convMock->expects(self::once())
            ->method('toStorageValue')
            ->with(
                self::isInstanceOf(
                    FieldValue::class
                ),
                self::isInstanceOf(
                    StorageFieldValue::class
                )
            )->will(self::returnValue(new StorageFieldValue()));

        $reg = new Registry(['some-type' => $convMock]);

        $field = new Field();
        $field->type = 'some-type';
        $field->value = new FieldValue();

        $mapper = new Mapper(
            $reg,
            $this->getLanguageHandler(),
            $this->getContentTypeHandler(),
            $this->getEventDispatcher(),
        );
        $res = $mapper->convertToStorageValue($field);

        self::assertInstanceOf(
            StorageFieldValue::class,
            $res
        );
    }

    public function testExtractContentFromRows()
    {
        $rowsFixture = $this->getContentExtractFixture();
        $nameRowsFixture = $this->getNamesExtractFixture();

        $contentType = $this->getContentTypeFromRows($rowsFixture);

        $contentTypeHandlerMock = $this->getContentTypeHandler();
        $contentTypeHandlerMock->method('load')->willReturn($contentType);

        $reg = $this->getFieldRegistry([
            'ibexa_author',
            'ibexa_string',
            'ibexa_boolean',
            'ibexa_image',
            'ibexa_datetime',
            'ibexa_keyword',
        ], count($rowsFixture) - 1);

        $mapper = new Mapper(
            $reg,
            $this->getLanguageHandler(),
            $contentTypeHandlerMock,
            $this->getEventDispatcher()
        );
        $result = $mapper->extractContentFromRows($rowsFixture, $nameRowsFixture);

        $expected = [$this->getContentExtractReference()];

        self::assertEquals(
            $expected,
            $result
        );
    }

    public function testExtractContentFromRowsWithNewFieldDefinitions(): void
    {
        $rowsFixture = $this->getContentExtractFixture();
        $nameRowsFixture = $this->getNamesExtractFixture();

        $contentType = $this->getContentTypeFromRows($rowsFixture);
        $contentType->fieldDefinitions[] = new Content\Type\FieldDefinition([
            'fieldType' => 'eznumber',
        ]);

        $contentTypeHandlerMock = $this->getContentTypeHandler();
        $contentTypeHandlerMock->method('load')->willReturn($contentType);

        $reg = $this->getFieldRegistry([
            'ibexa_author',
            'ibexa_string',
            'ibexa_boolean',
            'ibexa_image',
            'ibexa_datetime',
            'ibexa_keyword',
            'eznumber',
        ], count($rowsFixture) - 1);

        $mapper = new Mapper(
            $reg,
            $this->getLanguageHandler(),
            $contentTypeHandlerMock,
            $this->getEventDispatcher()
        );
        $result = $mapper->extractContentFromRows($rowsFixture, $nameRowsFixture);

        $expectedContent = $this->getContentExtractReference();
        $expectedContent->fields[] = new Field([
            'type' => 'eznumber',
            'languageCode' => 'eng-US',
            'value' => new FieldValue(),
            'versionNo' => 2,
        ]);

        self::assertEquals(
            [
                $expectedContent,
            ],
            $result
        );
    }

    public function testExtractContentFromRowsWithRemovedFieldDefinitions(): void
    {
        $rowsFixture = $this->getContentExtractFixture();
        $nameRowsFixture = $this->getNamesExtractFixture();

        $contentType = $this->getContentTypeFromRows($rowsFixture);
        $contentType->fieldDefinitions = array_filter(
            $contentType->fieldDefinitions,
            static function (Content\Type\FieldDefinition $fieldDefinition): bool {
                // ref. fixtures, ibexa_author
                return $fieldDefinition->id !== 185;
            }
        );

        $contentTypeHandlerMock = $this->getContentTypeHandler();
        $contentTypeHandlerMock->method('load')->willReturn($contentType);

        $reg = $this->getFieldRegistry([
            'ibexa_string',
            'ibexa_boolean',
            'ibexa_image',
            'ibexa_datetime',
            'ibexa_keyword',
        ], count($rowsFixture) - 2);

        $mapper = new Mapper(
            $reg,
            $this->getLanguageHandler(),
            $contentTypeHandlerMock,
            $this->getEventDispatcher()
        );
        $result = $mapper->extractContentFromRows($rowsFixture, $nameRowsFixture);

        $expectedContent = $this->getContentExtractReference();
        $expectedContent->fields = array_values(
            array_filter($expectedContent->fields, static function (Field $field): bool {
                return $field->fieldDefinitionId !== 185;
            })
        );

        self::assertEquals(
            [
                $expectedContent,
            ],
            $result
        );
    }

    public function testExtractContentFromRowsMultipleVersions()
    {
        $convMock = $this->createMock(Converter::class);
        $convMock->expects(self::any())
            ->method('toFieldValue')
            ->will(self::returnValue(new FieldValue()));

        $reg = new Registry(
            [
                'ibexa_string' => $convMock,
                'ibexa_datetime' => $convMock,
            ]
        );

        $rowsFixture = $this->getMultipleVersionsExtractFixture();
        $nameRowsFixture = $this->getMultipleVersionsNamesExtractFixture();

        $contentType = $this->getContentTypeFromRows($rowsFixture);

        $contentTypeHandlerMock = $this->getContentTypeHandler();
        $contentTypeHandlerMock->method('load')->willReturn($contentType);

        $mapper = new Mapper(
            $reg,
            $this->getLanguageHandler(),
            $contentTypeHandlerMock,
            $this->getEventDispatcher()
        );
        $result = $mapper->extractContentFromRows($rowsFixture, $nameRowsFixture);

        self::assertCount(
            2,
            $result
        );

        self::assertEquals(
            11,
            $result[0]->versionInfo->contentInfo->id
        );
        self::assertEquals(
            11,
            $result[1]->versionInfo->contentInfo->id
        );

        self::assertEquals(
            1,
            $result[0]->versionInfo->versionNo
        );
        self::assertEquals(
            2,
            $result[1]->versionInfo->versionNo
        );
    }

    /**
     * @param string[] $fieldTypeIdentifiers
     */
    private function getFieldRegistry(
        array $fieldTypeIdentifiers = [],
        ?int $expectedConverterCalls = null
    ): Registry {
        $converterMock = $this->createMock(Converter::class);
        $converterMock->expects(
            $expectedConverterCalls === null
                ? self::any()
                : self::exactly($expectedConverterCalls)
        )
            ->method('toFieldValue')
            ->willReturn(new FieldValue());

        $converters = [];
        foreach ($fieldTypeIdentifiers as $fieldTypeIdentifier) {
            $converters[$fieldTypeIdentifier] = $converterMock;
        }

        return new Registry($converters);
    }

    public function testCreateCreateStructFromContent()
    {
        $time = time();
        $mapper = $this->getMapper();

        $content = $this->getContentExtractReference();

        $struct = $mapper->createCreateStructFromContent($content);

        self::assertInstanceOf(CreateStruct::class, $struct);

        return [
            'original' => $content,
            'result' => $struct,
            'time' => $time,
        ];

        // parentLocations
        // fields
    }

    /**
     * @depends testCreateCreateStructFromContent
     */
    public function testCreateCreateStructFromContentBasicProperties($data)
    {
        $content = $data['original'];
        $struct = $data['result'];
        $time = $data['time'];
        $this->assertStructsEqual(
            $content->versionInfo->contentInfo,
            $struct,
            ['sectionId', 'ownerId']
        );
        self::assertNotEquals($content->versionInfo->contentInfo->remoteId, $struct->remoteId);
        self::assertSame($content->versionInfo->contentInfo->contentTypeId, $struct->typeId);
        self::assertSame(2, $struct->initialLanguageId);
        self::assertSame($content->versionInfo->contentInfo->alwaysAvailable, $struct->alwaysAvailable);
        self::assertGreaterThanOrEqual($time, $struct->modified);
    }

    /**
     * @depends testCreateCreateStructFromContent
     */
    public function testCreateCreateStructFromContentParentLocationsEmpty($data)
    {
        self::assertEquals(
            [],
            $data['result']->locations
        );
    }

    /**
     * @depends testCreateCreateStructFromContent
     */
    public function testCreateCreateStructFromContentFieldCount($data)
    {
        self::assertEquals(
            count($data['original']->fields),
            count($data['result']->fields)
        );
    }

    /**
     * @depends testCreateCreateStructFromContent
     */
    public function testCreateCreateStructFromContentFieldsNoId($data)
    {
        foreach ($data['result']->fields as $field) {
            self::assertNull($field->id);
        }
    }

    public function testExtractRelationsFromRows()
    {
        $mapper = $this->getMapper();

        $rows = $this->getRelationExtractFixture();

        $res = $mapper->extractRelationsFromRows($rows);

        self::assertEquals(
            $this->getRelationExtractReference(),
            $res
        );
    }

    public function testCreateCreateStructFromContentWithPreserveOriginalLanguage()
    {
        $time = time();
        $mapper = $this->getMapper();

        $content = $this->getContentExtractReference();
        $content->versionInfo->contentInfo->mainLanguageCode = 'eng-GB';

        $struct = $mapper->createCreateStructFromContent($content, true);

        self::assertInstanceOf(CreateStruct::class, $struct);
        $this->assertStructsEqual($content->versionInfo->contentInfo, $struct, ['sectionId', 'ownerId']);
        self::assertNotEquals($content->versionInfo->contentInfo->remoteId, $struct->remoteId);
        self::assertSame($content->versionInfo->contentInfo->contentTypeId, $struct->typeId);
        self::assertSame(2, $struct->initialLanguageId);
        self::assertSame(4, $struct->mainLanguageId);
        self::assertSame($content->versionInfo->contentInfo->alwaysAvailable, $struct->alwaysAvailable);
        self::assertGreaterThanOrEqual($time, $struct->modified);
    }

    /**
     * @dataProvider extractContentInfoFromRowProvider
     *
     * @param array $fixtures
     * @param string $prefix
     */
    public function testExtractContentInfoFromRow(array $fixtures, $prefix)
    {
        $contentInfoReference = $this->getContentExtractReference()->versionInfo->contentInfo;
        $mapper = new Mapper(
            $this->getValueConverterRegistryMock(),
            $this->getLanguageHandler(),
            $this->getContentTypeHandler(),
            $this->getEventDispatcher()
        );
        self::assertEquals($contentInfoReference, $mapper->extractContentInfoFromRow($fixtures, $prefix));
    }

    /**
     * Returns test data for {@link testExtractContentInfoFromRow()}.
     *
     * @return array
     */
    public function extractContentInfoFromRowProvider()
    {
        $fixtures = $this->getContentExtractFixture();
        $fixturesNoPrefix = [];
        foreach ($fixtures[0] as $key => $value) {
            $keyNoPrefix = $key === 'content_tree_main_node_id'
                ? $key
                : (string) preg_replace('/^content_/', '', $key);
            $fixturesNoPrefix[$keyNoPrefix] = $value;
        }

        return [
            [$fixtures[0], 'content_'],
            [$fixturesNoPrefix, ''],
        ];
    }

    public function testCreateRelationFromCreateStruct()
    {
        $struct = $this->getRelationCreateStructFixture();

        $mapper = $this->getMapper();
        $relation = $mapper->createRelationFromCreateStruct($struct);

        self::assertInstanceOf(SPIRelation::class, $relation);
        foreach ($struct as $property => $value) {
            self::assertSame($value, $relation->$property);
        }
    }

    /**
     * Returns test data for {@link testExtractVersionInfoFromRow()}.
     *
     * @return array
     */
    public function extractVersionInfoFromRowProvider()
    {
        $fixturesAll = $this->getContentExtractFixture();
        $fixtures = $fixturesAll[0];
        $fixtures['content_version_names'] = [
            ['content_translation' => 'eng-US', 'name' => 'Something'],
        ];
        $fixtures['content_version_languages'] = [2];
        $fixtures['content_version_initial_language_code'] = 'eng-US';
        $fixturesNoPrefix = [];
        foreach ($fixtures as $key => $value) {
            $keyNoPrefix = (string) str_replace('content_version_', '', $key);
            $fixturesNoPrefix[$keyNoPrefix] = $value;
        }

        return [
            [$fixtures, 'content_version_'],
            [$fixturesNoPrefix, ''],
        ];
    }

    /**
     * Returns a fixture of database rows for content extraction.
     *
     * Fixture is stored in _fixtures/extract_content_from_rows.php
     *
     * @return array
     */
    protected function getContentExtractFixture()
    {
        return require __DIR__ . '/_fixtures/extract_content_from_rows.php';
    }

    /**
     * Returns a fixture of database rows for content names extraction.
     *
     * Fixture is stored in _fixtures/extract_names_from_rows.php
     *
     * @return array
     */
    protected function getNamesExtractFixture()
    {
        return require __DIR__ . '/_fixtures/extract_names_from_rows.php';
    }

    /**
     * Returns a reference result for content extraction.
     *
     * Fixture is stored in _fixtures/extract_content_from_rows_result.php
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getContentExtractReference()
    {
        return require __DIR__ . '/_fixtures/extract_content_from_rows_result.php';
    }

    /**
     * Returns a fixture for mapping multiple versions of a content object.
     *
     * @return string[][]
     */
    protected function getMultipleVersionsExtractFixture()
    {
        return require __DIR__ . '/_fixtures/extract_content_from_rows_multiple_versions.php';
    }

    /**
     * Returns a fixture of database rows for content names extraction across multiple versions.
     *
     * Fixture is stored in _fixtures/extract_names_from_rows_multiple_versions.php
     *
     * @return array
     */
    protected function getMultipleVersionsNamesExtractFixture()
    {
        return require __DIR__ . '/_fixtures/extract_names_from_rows_multiple_versions.php';
    }

    /**
     * Returns a fixture of database rows for relations extraction.
     *
     * Fixture is stored in _fixtures/relations.php
     *
     * @return array
     */
    protected function getRelationExtractFixture()
    {
        return require __DIR__ . '/_fixtures/relations_rows.php';
    }

    /**
     * Returns a reference result for content extraction.
     *
     * Fixture is stored in _fixtures/relations_results.php
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getRelationExtractReference()
    {
        return require __DIR__ . '/_fixtures/relations_results.php';
    }

    /**
     * Returns a Mapper.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Mapper
     */
    protected function getMapper($valueConverter = null)
    {
        return new Mapper(
            $this->getValueConverterRegistryMock(),
            $this->getLanguageHandler(),
            $this->getContentTypeHandler(),
            $this->getEventDispatcher()
        );
    }

    /**
     * Returns a FieldValue converter registry mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry
     */
    protected function getValueConverterRegistryMock()
    {
        if (!isset($this->valueConverterRegistryMock)) {
            $this->valueConverterRegistryMock = $this->getMockBuilder(Registry::class)
                ->setMethods([])
                ->getMock();

            $this->valueConverterRegistryMock
                ->method('getConverter')
                ->willReturn($this->createMock(Converter::class));
        }

        return $this->valueConverterRegistryMock;
    }

    /**
     * Returns a {@see \Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct} fixture.
     */
    protected function getRelationCreateStructFixture(): RelationCreateStruct
    {
        $struct = new RelationCreateStruct();

        $struct->destinationContentId = 0;
        $struct->sourceContentId = 0;
        $struct->sourceContentVersionNo = 1;
        $struct->sourceFieldDefinitionId = 1;
        $struct->type = RelationType::COMMON->value;

        return $struct;
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(
            new ResolveVirtualFieldSubscriber(
                $this->getValueConverterRegistryMock(),
                $this->createMock(StorageRegistry::class),
                $this->createMock(Gateway::class)
            )
        );

        return $eventDispatcher;
    }

    /**
     * Returns a language handler mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Language\Handler&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLanguageHandler(): Language\Handler
    {
        $languages = [
            'eng-US' => new Language(
                [
                    'id' => 2,
                    'languageCode' => 'eng-US',
                    'name' => 'US english',
                ]
            ),
            'eng-GB' => new Language(
                [
                    'id' => 4,
                    'languageCode' => 'eng-GB',
                    'name' => 'British english',
                ]
            ),
        ];

        if (!isset($this->languageHandler)) {
            $this->languageHandler = $this->createMock(Language\Handler::class);
            $this->languageHandler->expects(self::any())
                ->method('load')
                ->will(
                    self::returnCallback(
                        static function ($id) use ($languages) {
                            foreach ($languages as $language) {
                                if ($language->id == $id) {
                                    return $language;
                                }
                            }

                            return null;
                        }
                    )
                );
            $this->languageHandler->expects(self::any())
                ->method('loadByLanguageCode')
                ->will(
                    self::returnCallback(
                        static function ($languageCode) use ($languages) {
                            foreach ($languages as $language) {
                                if ($language->languageCode == $languageCode) {
                                    return $language;
                                }
                            }

                            return null;
                        }
                    )
                );
            $this->languageHandler->expects(self::any())
                ->method('loadAll')
                ->willReturn($languages);
        }

        return $this->languageHandler;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Handler&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getContentTypeHandler(): Content\Type\Handler
    {
        return $this->createMock(Content\Type\Handler::class);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function getContentTypeFromRows(array $rows): Content\Type
    {
        $contentType = new Content\Type();
        $fieldDefinitions = [];

        foreach ($rows as $row) {
            $fieldDefinitionId = $row['content_field_contentclassattribute_id'];
            $fieldType = $row['content_field_data_type_string'];

            if (isset($fieldDefinitions[$fieldDefinitionId])) {
                continue;
            }

            $fieldDefinitions[$fieldDefinitionId] = new Content\Type\FieldDefinition([
                'id' => $fieldDefinitionId,
                'fieldType' => $fieldType,
            ]);
        }

        $contentType->fieldDefinitions = array_values($fieldDefinitions);

        return $contentType;
    }
}
