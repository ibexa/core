<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Search;

use ArrayObject;
use Ibexa\Contracts\Core\FieldType\Indexable;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPIContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion as APICriterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CustomFieldInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause as APISortClause;
use Ibexa\Contracts\Core\Search\FieldType;
use Ibexa\Contracts\Core\Search\FieldType as SPIFieldType;
use Ibexa\Core\Search\Common\FieldNameGenerator;
use Ibexa\Core\Search\Common\FieldNameResolver;
use Ibexa\Core\Search\Common\FieldRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(FieldNameResolver::class)]
class FieldNameResolverTest extends TestCase
{
    public function testGetFieldNamesReturnsEmptyArray(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap', 'getIndexFieldName']);
        $criterionMock = $this->getCriterionMock();

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getSearchableFieldMap')
            ->will(
                $this->returnValue(
                    [
                        'content_type_identifier_1' => [
                            'field_definition_identifier_1' => [
                                'field_definition_id' => 'field_definition_id_1',
                                'field_type_identifier' => 'field_type_identifier_1',
                            ],
                        ],
                        'content_type_identifier_2' => [
                            'field_definition_identifier_2' => [
                                'field_definition_id' => 'field_definition_id_2',
                                'field_type_identifier' => 'field_type_identifier_2',
                            ],
                        ],
                    ]
                )
            );

        $fieldNames = $mockedFieldNameResolver->getFieldTypes(
            $criterionMock,
            'field_definition_identifier_1',
            'field_type_identifier_2',
            'field_name'
        );

        self::assertIsArray($fieldNames);
        self::assertEmpty($fieldNames);
    }

    public function testGetFieldNames(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap', 'getIndexFieldName']);
        $criterionMock = $this->getCriterionMock();

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getSearchableFieldMap')
            ->will(
                $this->returnValue(
                    [
                        'content_type_identifier_1' => [
                            'field_definition_identifier_1' => [
                                'field_definition_id' => 'field_definition_id_1',
                                'field_type_identifier' => 'field_type_identifier_1',
                            ],
                        ],
                        'content_type_identifier_2' => [
                            'field_definition_identifier_1' => [
                                'field_definition_id' => 'field_definition_id_2',
                                'field_type_identifier' => 'field_type_identifier_2',
                            ],
                            'field_definition_identifier_2' => [
                                'field_definition_id' => 'field_definition_id_3',
                                'field_type_identifier' => 'field_type_identifier_3',
                            ],
                        ],
                    ]
                )
            );

        $getIndexFieldNameMatcher = $this->exactly(2);
        $mockedFieldNameResolver
            ->expects($getIndexFieldNameMatcher)
            ->method('getIndexFieldName')
            ->willReturnCallback(static function (
                $criterion,
                $contentTypeIdentifier,
                $fieldDefinitionIdentifier,
                $fieldTypeIdentifier,
                $fieldName
            ) use ($getIndexFieldNameMatcher): array {
                self::assertInstanceOf(APICriterion::class, $criterion);
                self::assertSame('field_definition_identifier_1', $fieldDefinitionIdentifier);
                self::assertNull($fieldName);

                if ($getIndexFieldNameMatcher->numberOfInvocations() === 1) {
                    self::assertSame('content_type_identifier_1', $contentTypeIdentifier);
                    self::assertSame('field_type_identifier_1', $fieldTypeIdentifier);

                    return ['index_field_name_1' => null];
                }

                self::assertSame('content_type_identifier_2', $contentTypeIdentifier);
                self::assertSame('field_type_identifier_2', $fieldTypeIdentifier);

                return ['index_field_name_2' => null];
            });

        $fieldNames = $mockedFieldNameResolver->getFieldTypes(
            $criterionMock,
            'field_definition_identifier_1'
        );

        self::assertIsArray($fieldNames);
        self::assertEquals(
            [
                'index_field_name_1' => null,
                'index_field_name_2' => null,
            ],
            $fieldNames
        );
    }

    public function testGetFieldNamesWithNamedField(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap', 'getIndexFieldName']);
        $criterionMock = $this->getCriterionMock();

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getSearchableFieldMap')
            ->will(
                $this->returnValue(
                    [
                        'content_type_identifier_1' => [
                            'field_definition_identifier_1' => [
                                'field_definition_id' => 'field_definition_id_1',
                                'field_type_identifier' => 'field_type_identifier_1',
                            ],
                        ],
                        'content_type_identifier_2' => [
                            'field_definition_identifier_1' => [
                                'field_definition_id' => 'field_definition_id_2',
                                'field_type_identifier' => 'field_type_identifier_2',
                            ],
                            'field_definition_identifier_2' => [
                                'field_definition_id' => 'field_definition_id_3',
                                'field_type_identifier' => 'field_type_identifier_3',
                            ],
                        ],
                    ]
                )
            );

        $getIndexFieldNameMatcher = $this->exactly(2);
        $mockedFieldNameResolver
            ->expects($getIndexFieldNameMatcher)
            ->method('getIndexFieldName')
            ->willReturnCallback(static function (
                $criterion,
                $contentTypeIdentifier,
                $fieldDefinitionIdentifier,
                $fieldTypeIdentifier,
                $fieldName
            ) use ($getIndexFieldNameMatcher): array {
                self::assertInstanceOf(APICriterion::class, $criterion);
                self::assertSame('field_definition_identifier_1', $fieldDefinitionIdentifier);
                self::assertSame('field_name', $fieldName);

                if ($getIndexFieldNameMatcher->numberOfInvocations() === 1) {
                    self::assertSame('content_type_identifier_1', $contentTypeIdentifier);
                    self::assertSame('field_type_identifier_1', $fieldTypeIdentifier);

                    return ['index_field_name_1' => null];
                }

                self::assertSame('content_type_identifier_2', $contentTypeIdentifier);
                self::assertSame('field_type_identifier_2', $fieldTypeIdentifier);

                return ['index_field_name_2' => null];
            });

        $fieldNames = $mockedFieldNameResolver->getFieldTypes(
            $criterionMock,
            'field_definition_identifier_1',
            null,
            'field_name'
        );

        self::assertIsArray($fieldNames);
        self::assertEquals(
            [
                'index_field_name_1' => null,
                'index_field_name_2' => null,
            ],
            $fieldNames
        );
    }

    public function testGetFieldNamesWithTypedField(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap', 'getIndexFieldName']);
        $criterionMock = $this->getCriterionMock();

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getSearchableFieldMap')
            ->will(
                $this->returnValue(
                    [
                        'content_type_identifier_1' => [
                            'field_definition_identifier_1' => [
                                'field_definition_id' => 'field_definition_id_1',
                                'field_type_identifier' => 'field_type_identifier_1',
                            ],
                        ],
                        'content_type_identifier_2' => [
                            'field_definition_identifier_1' => [
                                'field_definition_id' => 'field_definition_id_2',
                                'field_type_identifier' => 'field_type_identifier_2',
                            ],
                            'field_definition_identifier_2' => [
                                'field_definition_id' => 'field_definition_id_3',
                                'field_type_identifier' => 'field_type_identifier_3',
                            ],
                        ],
                    ]
                )
            );

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getIndexFieldName')
            ->with(
                self::isInstanceOf(
                    APICriterion::class
                ),
                'content_type_identifier_2',
                'field_definition_identifier_1',
                'field_type_identifier_2',
                null
            )
            ->will($this->returnValue(['index_field_name_1' => null]));

        $fieldNames = $mockedFieldNameResolver->getFieldTypes(
            $criterionMock,
            'field_definition_identifier_1',
            'field_type_identifier_2',
            null
        );

        self::assertIsArray($fieldNames);
        self::assertEquals(
            [
                'index_field_name_1' => null,
            ],
            $fieldNames
        );
    }

    public function testGetFieldNamesWithTypedAndNamedField(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap', 'getIndexFieldName']);
        $criterionMock = $this->getCriterionMock();

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getSearchableFieldMap')
            ->will(
                $this->returnValue(
                    [
                        'content_type_identifier_1' => [
                            'field_definition_identifier_1' => [
                                'field_definition_id' => 'field_definition_id_1',
                                'field_type_identifier' => 'field_type_identifier_1',
                            ],
                        ],
                        'content_type_identifier_2' => [
                            'field_definition_identifier_1' => [
                                'field_definition_id' => 'field_definition_id_2',
                                'field_type_identifier' => 'field_type_identifier_2',
                            ],
                            'field_definition_identifier_2' => [
                                'field_definition_id' => 'field_definition_id_3',
                                'field_type_identifier' => 'field_type_identifier_3',
                            ],
                        ],
                    ]
                )
            );

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getIndexFieldName')
            ->with(
                self::isInstanceOf(
                    APICriterion::class
                ),
                'content_type_identifier_2',
                'field_definition_identifier_1',
                'field_type_identifier_2',
                'field_name'
            )
            ->will($this->returnValue(['index_field_name_1' => null]));

        $fieldNames = $mockedFieldNameResolver->getFieldTypes(
            $criterionMock,
            'field_definition_identifier_1',
            'field_type_identifier_2',
            'field_name'
        );

        self::assertIsArray($fieldNames);
        self::assertEquals(
            [
                'index_field_name_1' => null,
            ],
            $fieldNames
        );
    }

    public function testGetSortFieldName(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap', 'getIndexFieldName']);
        $sortClauseMock = $this->getSortClauseMock();

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getSearchableFieldMap')
            ->will(
                $this->returnValue(
                    [
                        'content_type_identifier' => [
                            'field_definition_identifier' => [
                                'field_definition_id' => 'field_definition_id',
                                'field_type_identifier' => 'field_type_identifier',
                            ],
                        ],
                    ]
                )
            );

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getIndexFieldName')
            ->with(
                self::isInstanceOf(
                    APISortClause::class
                ),
                'content_type_identifier',
                'field_definition_identifier',
                'field_type_identifier',
                'field_name'
            )
            ->will($this->returnValue(['index_field_name' => null]));

        $fieldName = $mockedFieldNameResolver->getSortFieldName(
            $sortClauseMock,
            'content_type_identifier',
            'field_definition_identifier',
            'field_name'
        );

        self::assertEquals('index_field_name', $fieldName);
    }

    public function testGetSortFieldNameReturnsNull(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap', 'getIndexFieldName']);
        $sortClauseMock = $this->getSortClauseMock();

        $mockedFieldNameResolver
            ->expects($this->once())
            ->method('getSearchableFieldMap')
            ->will(
                $this->returnValue(
                    [
                        'content_type_identifier' => [
                            'field_definition_identifier' => [
                                'field_definition_id' => 'field_definition_id',
                                'field_type_identifier' => 'field_type_identifier',
                            ],
                        ],
                    ]
                )
            );

        $fieldName = $mockedFieldNameResolver->getSortFieldName(
            $sortClauseMock,
            'non_existent_content_type_identifier',
            'non_existent_field_definition_identifier',
            'field_name'
        );

        self::assertNull($fieldName);
    }

    public function testGetIndexFieldNameCustomField(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap']);

        $customFieldMock = $this->createMock(CustomFieldInterface::class);
        $customFieldMock
            ->expects($this->once())
            ->method('getCustomField')
            ->with(
                'content_type_identifier',
                'field_definition_identifier'
            )
            ->will(
                $this->returnValue('custom_field_name')
            );

        $customFieldName = $mockedFieldNameResolver->getIndexFieldName(
            $customFieldMock,
            'content_type_identifier',
            'field_definition_identifier',
            'dummy',
            'dummy',
            false
        );

        self::assertEquals('custom_field_name', key($customFieldName));
    }

    public function testGetIndexFieldNameNamedField(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap']);
        $indexFieldType = $this->getIndexFieldTypeMock();
        $searchFieldTypeMock = $this->getSearchFieldTypeMock();

        $this->fieldRegistryMock
            ->expects($this->once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                $this->returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects($this->once())
            ->method('getIndexDefinition')
            ->will(
                $this->returnValue(
                    [
                        'field_name' => $searchFieldTypeMock,
                    ]
                )
            );

        $indexFieldType->expects($this->never())->method('getDefaultSortField');

        $this->fieldNameGeneratorMock
            ->expects($this->once())
            ->method('getName')
            ->with(
                'field_name',
                'field_definition_identifier',
                'content_type_identifier'
            )
            ->will(
                $this->returnValue('generated_field_name')
            );

        $this->fieldNameGeneratorMock
            ->expects($this->once())
            ->method('getTypedName')
            ->with(
                'generated_field_name',
                self::isInstanceOf(SPIFieldType::class)
            )
            ->will(
                $this->returnValue('generated_typed_field_name')
            );

        $fieldName = $mockedFieldNameResolver->getIndexFieldName(
            new ArrayObject(),
            'content_type_identifier',
            'field_definition_identifier',
            'field_type_identifier',
            'field_name',
            true
        );

        self::assertEquals('generated_typed_field_name', key($fieldName));
    }

    public function testGetIndexFieldNameDefaultMatchField(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap']);
        $indexFieldType = $this->getIndexFieldTypeMock();
        $searchFieldTypeMock = $this->getSearchFieldTypeMock();

        $this->fieldRegistryMock
            ->expects($this->once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                $this->returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects($this->once())
            ->method('getDefaultMatchField')
            ->will(
                $this->returnValue('field_name')
            );

        $indexFieldType
            ->expects($this->once())
            ->method('getIndexDefinition')
            ->will(
                $this->returnValue(
                    [
                        'field_name' => $searchFieldTypeMock,
                    ]
                )
            );

        $this->fieldNameGeneratorMock
            ->expects($this->once())
            ->method('getName')
            ->with(
                'field_name',
                'field_definition_identifier',
                'content_type_identifier'
            )
            ->will(
                $this->returnValue('generated_field_name')
            );

        $this->fieldNameGeneratorMock
            ->expects($this->once())
            ->method('getTypedName')
            ->with(
                'generated_field_name',
                self::isInstanceOf(SPIFieldType::class)
            )
            ->will(
                $this->returnValue('generated_typed_field_name')
            );

        $fieldName = $mockedFieldNameResolver->getIndexFieldName(
            new ArrayObject(),
            'content_type_identifier',
            'field_definition_identifier',
            'field_type_identifier',
            null,
            false
        );

        self::assertEquals('generated_typed_field_name', key($fieldName));
    }

    public function testGetIndexFieldNameDefaultSortField(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap']);
        $indexFieldType = $this->getIndexFieldTypeMock();
        $searchFieldTypeMock = $this->getSearchFieldTypeMock();

        $this->fieldRegistryMock
            ->expects($this->once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                $this->returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects($this->once())
            ->method('getDefaultSortField')
            ->will(
                $this->returnValue('field_name')
            );

        $indexFieldType
            ->expects($this->once())
            ->method('getIndexDefinition')
            ->will(
                $this->returnValue(
                    [
                        'field_name' => $searchFieldTypeMock,
                    ]
                )
            );

        $this->fieldNameGeneratorMock
            ->expects($this->once())
            ->method('getName')
            ->with(
                'field_name',
                'field_definition_identifier',
                'content_type_identifier'
            )
            ->will(
                $this->returnValue('generated_field_name')
            );

        $this->fieldNameGeneratorMock
            ->expects($this->once())
            ->method('getTypedName')
            ->with(
                'generated_field_name',
                self::isInstanceOf(SPIFieldType::class)
            )
            ->will(
                $this->returnValue('generated_typed_field_name')
            );

        $fieldName = $mockedFieldNameResolver->getIndexFieldName(
            new ArrayObject(),
            'content_type_identifier',
            'field_definition_identifier',
            'field_type_identifier',
            null,
            true
        );

        self::assertEquals('generated_typed_field_name', key($fieldName));
    }

    public function testGetIndexFieldNameDefaultMatchFieldThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap']);
        $indexFieldType = $this->getIndexFieldTypeMock();
        $searchFieldTypeMock = $this->getSearchFieldTypeMock();

        $this->fieldRegistryMock
            ->expects($this->once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                $this->returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects($this->once())
            ->method('getDefaultMatchField')
            ->will(
                $this->returnValue('non_existent_field_name')
            );

        $indexFieldType
            ->expects($this->once())
            ->method('getIndexDefinition')
            ->will(
                $this->returnValue(
                    [
                        'field_name' => $searchFieldTypeMock,
                    ]
                )
            );

        $mockedFieldNameResolver->getIndexFieldName(
            new ArrayObject(),
            'content_type_identifier',
            'field_definition_identifier',
            'field_type_identifier',
            null,
            false
        );
    }

    public function testGetIndexFieldNameDefaultSortFieldThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap']);
        $indexFieldType = $this->getIndexFieldTypeMock();
        $searchFieldTypeMock = $this->getSearchFieldTypeMock();

        $this->fieldRegistryMock
            ->expects($this->once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                $this->returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects($this->once())
            ->method('getDefaultSortField')
            ->will(
                $this->returnValue('non_existent_field_name')
            );

        $indexFieldType
            ->expects($this->once())
            ->method('getIndexDefinition')
            ->will(
                $this->returnValue(
                    [
                        'field_name' => $searchFieldTypeMock,
                    ]
                )
            );

        $mockedFieldNameResolver->getIndexFieldName(
            new ArrayObject(),
            'content_type_identifier',
            'field_definition_identifier',
            'field_type_identifier',
            null,
            true
        );
    }

    public function testGetIndexFieldNameNamedFieldThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);

        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(
            ['getSortFieldName', 'getSearchableFieldMap', 'getFieldTypes']
        );
        $indexFieldType = $this->getIndexFieldTypeMock();
        $searchFieldTypeMock = $this->getSearchFieldTypeMock();

        $this->fieldRegistryMock
            ->expects($this->once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                $this->returnValue($indexFieldType)
            );

        $indexFieldType->expects($this->never())->method('getDefaultMatchField');

        $indexFieldType
            ->expects($this->once())
            ->method('getIndexDefinition')
            ->will(
                $this->returnValue(
                    [
                        'field_name' => $searchFieldTypeMock,
                    ]
                )
            );

        $mockedFieldNameResolver->getIndexFieldName(
            new ArrayObject(),
            'content_type_identifier',
            'field_definition_identifier',
            'field_type_identifier',
            'non_existent_field_name',
            false
        );
    }

    /**
     * @param array $methods
     *
     * @return FieldNameResolver|MockObject
     */
    protected function getMockedFieldNameResolver(array $methods = [])
    {
        $builder = $this
            ->getMockBuilder(FieldNameResolver::class)
            ->setConstructorArgs(
                [
                    $this->getFieldRegistryMock(),
                    $this->getContentTypeHandlerMock(),
                    $this->getFieldNameGeneratorMock(),
                ]
            );
        if ($methods !== []) {
            $builder->onlyMethods(array_values($methods));
        }
        $fieldNameResolver = $builder->getMock();

        return $fieldNameResolver;
    }

    /** @var FieldRegistry|MockObject */
    protected $fieldRegistryMock;

    /**
     * @return FieldRegistry|MockObject
     */
    protected function getFieldRegistryMock()
    {
        if (!isset($this->fieldRegistryMock)) {
            $this->fieldRegistryMock = $this->createMock(FieldRegistry::class);
        }

        return $this->fieldRegistryMock;
    }

    /**
     * @return Indexable|MockObject
     */
    protected function getIndexFieldTypeMock()
    {
        return $this->createMock(Indexable::class);
    }

    /**
     * @return FieldType|MockObject
     */
    protected function getSearchFieldTypeMock()
    {
        return $this->createMock(SPIFieldType::class);
    }

    /** @var Handler|MockObject */
    protected $contentTypeHandlerMock;

    /**
     * @return Handler|MockObject
     */
    protected function getContentTypeHandlerMock()
    {
        if (!isset($this->contentTypeHandlerMock)) {
            $this->contentTypeHandlerMock = $this->createMock(SPIContentTypeHandler::class);
        }

        return $this->contentTypeHandlerMock;
    }

    /** @var FieldNameGenerator|MockObject */
    protected $fieldNameGeneratorMock;

    /**
     * @return FieldNameGenerator|MockObject
     */
    protected function getFieldNameGeneratorMock()
    {
        if (!isset($this->fieldNameGeneratorMock)) {
            $this->fieldNameGeneratorMock = $this->createMock(FieldNameGenerator::class);
        }

        return $this->fieldNameGeneratorMock;
    }

    /**
     * @return Criterion|MockObject
     */
    protected function getCriterionMock()
    {
        return $this->createMock(APICriterion::class);
    }

    /**
     * @return SortClause|MockObject
     */
    protected function getSortClauseMock()
    {
        return $this->createMock(APISortClause::class);
    }
}
