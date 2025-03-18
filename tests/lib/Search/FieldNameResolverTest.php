<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Search;

use ArrayObject;
use Ibexa\Contracts\Core\FieldType\Indexable;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as SPIContentTypeHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion as APICriterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CustomFieldInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause as APISortClause;
use Ibexa\Contracts\Core\Search\FieldType as SPIFieldType;
use Ibexa\Core\Search\Common\FieldNameGenerator;
use Ibexa\Core\Search\Common\FieldNameResolver;
use Ibexa\Core\Search\Common\FieldRegistry;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\Search\Common\FieldNameResolver
 */
class FieldNameResolverTest extends TestCase
{
    public function testGetFieldNamesReturnsEmptyArray(): void
    {
        $mockedFieldNameResolver = $this->getMockedFieldNameResolver(['getSearchableFieldMap', 'getIndexFieldName']);
        $criterionMock = $this->getCriterionMock();

        $mockedFieldNameResolver
            ->expects(self::once())
            ->method('getSearchableFieldMap')
            ->will(
                self::returnValue(
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
            ->expects(self::once())
            ->method('getSearchableFieldMap')
            ->will(
                self::returnValue(
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
            ->expects(self::at(1))
            ->method('getIndexFieldName')
            ->with(
                self::isInstanceOf(
                    APICriterion::class
                ),
                'content_type_identifier_1',
                'field_definition_identifier_1',
                'field_type_identifier_1',
                null
            )
            ->will(self::returnValue(['index_field_name_1' => null]));

        $mockedFieldNameResolver
            ->expects(self::at(2))
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
            ->will(self::returnValue(['index_field_name_2' => null]));

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
            ->expects(self::once())
            ->method('getSearchableFieldMap')
            ->will(
                self::returnValue(
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
            ->expects(self::at(1))
            ->method('getIndexFieldName')
            ->with(
                self::isInstanceOf(
                    APICriterion::class
                ),
                'content_type_identifier_1',
                'field_definition_identifier_1',
                'field_type_identifier_1',
                'field_name'
            )
            ->will(self::returnValue(['index_field_name_1' => null]));

        $mockedFieldNameResolver
            ->expects(self::at(2))
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
            ->will(self::returnValue(['index_field_name_2' => null]));

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
            ->expects(self::once())
            ->method('getSearchableFieldMap')
            ->will(
                self::returnValue(
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
            ->expects(self::at(1))
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
            ->will(self::returnValue(['index_field_name_1' => null]));

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
            ->expects(self::once())
            ->method('getSearchableFieldMap')
            ->will(
                self::returnValue(
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
            ->expects(self::at(1))
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
            ->will(self::returnValue(['index_field_name_1' => null]));

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
            ->expects(self::once())
            ->method('getSearchableFieldMap')
            ->will(
                self::returnValue(
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
            ->expects(self::once())
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
            ->will(self::returnValue(['index_field_name' => null]));

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
            ->expects(self::once())
            ->method('getSearchableFieldMap')
            ->will(
                self::returnValue(
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
            ->expects(self::once())
            ->method('getCustomField')
            ->with(
                'content_type_identifier',
                'field_definition_identifier'
            )
            ->will(
                self::returnValue('custom_field_name')
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
            ->expects(self::once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                self::returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects(self::once())
            ->method('getIndexDefinition')
            ->will(
                self::returnValue(
                    [
                        'field_name' => $searchFieldTypeMock,
                    ]
                )
            );

        $indexFieldType->expects(self::never())->method('getDefaultSortField');

        $this->fieldNameGeneratorMock
            ->expects(self::once())
            ->method('getName')
            ->with(
                'field_name',
                'field_definition_identifier',
                'content_type_identifier'
            )
            ->will(
                self::returnValue('generated_field_name')
            );

        $this->fieldNameGeneratorMock
            ->expects(self::once())
            ->method('getTypedName')
            ->with(
                'generated_field_name',
                self::isInstanceOf(SPIFieldType::class)
            )
            ->will(
                self::returnValue('generated_typed_field_name')
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
            ->expects(self::once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                self::returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects(self::once())
            ->method('getDefaultMatchField')
            ->will(
                self::returnValue('field_name')
            );

        $indexFieldType
            ->expects(self::once())
            ->method('getIndexDefinition')
            ->will(
                self::returnValue(
                    [
                        'field_name' => $searchFieldTypeMock,
                    ]
                )
            );

        $this->fieldNameGeneratorMock
            ->expects(self::once())
            ->method('getName')
            ->with(
                'field_name',
                'field_definition_identifier',
                'content_type_identifier'
            )
            ->will(
                self::returnValue('generated_field_name')
            );

        $this->fieldNameGeneratorMock
            ->expects(self::once())
            ->method('getTypedName')
            ->with(
                'generated_field_name',
                self::isInstanceOf(SPIFieldType::class)
            )
            ->will(
                self::returnValue('generated_typed_field_name')
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
            ->expects(self::once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                self::returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects(self::once())
            ->method('getDefaultSortField')
            ->will(
                self::returnValue('field_name')
            );

        $indexFieldType
            ->expects(self::once())
            ->method('getIndexDefinition')
            ->will(
                self::returnValue(
                    [
                        'field_name' => $searchFieldTypeMock,
                    ]
                )
            );

        $this->fieldNameGeneratorMock
            ->expects(self::once())
            ->method('getName')
            ->with(
                'field_name',
                'field_definition_identifier',
                'content_type_identifier'
            )
            ->will(
                self::returnValue('generated_field_name')
            );

        $this->fieldNameGeneratorMock
            ->expects(self::once())
            ->method('getTypedName')
            ->with(
                'generated_field_name',
                self::isInstanceOf(SPIFieldType::class)
            )
            ->will(
                self::returnValue('generated_typed_field_name')
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
            ->expects(self::once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                self::returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects(self::once())
            ->method('getDefaultMatchField')
            ->will(
                self::returnValue('non_existent_field_name')
            );

        $indexFieldType
            ->expects(self::once())
            ->method('getIndexDefinition')
            ->will(
                self::returnValue(
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
            ->expects(self::once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                self::returnValue($indexFieldType)
            );

        $indexFieldType
            ->expects(self::once())
            ->method('getDefaultSortField')
            ->will(
                self::returnValue('non_existent_field_name')
            );

        $indexFieldType
            ->expects(self::once())
            ->method('getIndexDefinition')
            ->will(
                self::returnValue(
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
            ->expects(self::once())
            ->method('getType')
            ->with('field_type_identifier')
            ->will(
                self::returnValue($indexFieldType)
            );

        $indexFieldType->expects(self::never())->method('getDefaultMatchField');

        $indexFieldType
            ->expects(self::once())
            ->method('getIndexDefinition')
            ->will(
                self::returnValue(
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
     * @return \Ibexa\Core\Search\Common\FieldNameResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockedFieldNameResolver(array $methods = []): MockObject
    {
        $fieldNameResolver = $this
            ->getMockBuilder(FieldNameResolver::class)
            ->setConstructorArgs(
                [
                    $this->getFieldRegistryMock(),
                    $this->getContentTypeHandlerMock(),
                    $this->getFieldNameGeneratorMock(),
                ]
            )
            ->setMethods($methods)
            ->getMock();

        return $fieldNameResolver;
    }

    /** @var \Ibexa\Core\Search\Common\FieldRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected ?MockObject $fieldRegistryMock = null;

    /**
     * @return \Ibexa\Core\Search\Common\FieldRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFieldRegistryMock()
    {
        if (!isset($this->fieldRegistryMock)) {
            $this->fieldRegistryMock = $this->createMock(FieldRegistry::class);
        }

        return $this->fieldRegistryMock;
    }

    /**
     * @return \Ibexa\Contracts\Core\FieldType\Indexable|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getIndexFieldTypeMock(): MockObject
    {
        return $this->createMock(Indexable::class);
    }

    /**
     * @return \Ibexa\Contracts\Core\Search\FieldType|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSearchFieldTypeMock(): MockObject
    {
        return $this->createMock(SPIFieldType::class);
    }

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Type\Handler|\PHPUnit\Framework\MockObject\MockObject */
    protected ?MockObject $contentTypeHandlerMock = null;

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Handler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getContentTypeHandlerMock()
    {
        if (!isset($this->contentTypeHandlerMock)) {
            $this->contentTypeHandlerMock = $this->createMock(SPIContentTypeHandler::class);
        }

        return $this->contentTypeHandlerMock;
    }

    /** @var \Ibexa\Core\Search\Common\FieldNameGenerator|\PHPUnit\Framework\MockObject\MockObject */
    protected ?MockObject $fieldNameGeneratorMock = null;

    /**
     * @return \Ibexa\Core\Search\Common\FieldNameGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFieldNameGeneratorMock()
    {
        if (!isset($this->fieldNameGeneratorMock)) {
            $this->fieldNameGeneratorMock = $this->createMock(FieldNameGenerator::class);
        }

        return $this->fieldNameGeneratorMock;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getCriterionMock(): MockObject
    {
        return $this->createMock(APICriterion::class);
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSortClauseMock(): MockObject
    {
        return $this->createMock(APISortClause::class);
    }
}
