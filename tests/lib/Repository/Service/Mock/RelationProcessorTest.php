<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\FieldType\Value;
use Ibexa\Core\Repository\FieldTypeService;
use Ibexa\Core\Repository\Helper\RelationProcessor;
use Ibexa\Core\Repository\Values\Content\Relation as RelationValue;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @covers \Ibexa\Core\Repository\Helper\RelationProcessor
 */
class RelationProcessorTest extends BaseServiceMockTest
{
    public function providerForTestAppendRelations()
    {
        return [
            [
                [Relation::FIELD => [100]],
                [Relation::FIELD => [42 => [100 => 0]]],
            ],
            [
                [Relation::LINK => ['contentIds' => [100]]],
                [Relation::LINK => [100 => 0]],
            ],
            [
                [Relation::EMBED => ['contentIds' => [100]]],
                [Relation::EMBED => [100 => 0]],
            ],
            [
                [Relation::ASSET => [100]],
                [Relation::ASSET => [42 => [100 => 0]]],
            ],
            [
                [
                    Relation::FIELD => [100],
                    Relation::LINK => ['contentIds' => [100]],
                    Relation::EMBED => ['contentIds' => [100]],
                ],
                [
                    Relation::FIELD => [42 => [100 => 0]],
                    Relation::LINK => [100 => 0],
                    Relation::EMBED => [100 => 0],
                ],
            ],
            [
                [Relation::LINK => ['locationIds' => [100]]],
                [Relation::LINK => [200 => true]],
            ],
            [
                [
                    Relation::LINK => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                ],
                [Relation::LINK => [100 => 0, 200 => true]],
            ],
            [
                [Relation::EMBED => ['locationIds' => [100]]],
                [Relation::EMBED => [200 => true]],
            ],
            [
                [
                    Relation::EMBED => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                ],
                [Relation::EMBED => [100 => 0, 200 => true]],
            ],
            [
                [
                    Relation::LINK => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                    Relation::EMBED => [
                        'locationIds' => [101],
                        'contentIds' => [100],
                    ],
                ],
                [
                    Relation::LINK => [100 => 0, 200 => true],
                    Relation::EMBED => [100 => 0, 201 => true],
                ],
            ],
            [
                [
                    Relation::FIELD => [100],
                    Relation::LINK => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                    Relation::EMBED => [
                        'locationIds' => [101],
                        'contentIds' => [100],
                    ],
                ],
                [
                    Relation::FIELD => [42 => [100 => 0]],
                    Relation::LINK => [100 => 0, 200 => true],
                    Relation::EMBED => [100 => 0, 201 => true],
                ],
            ],
            [
                [
                    Relation::ASSET => [100],
                    Relation::LINK => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                    Relation::EMBED => [
                        'locationIds' => [101],
                        'contentIds' => [100],
                    ],
                ],
                [
                    Relation::ASSET => [42 => [100 => 0]],
                    Relation::LINK => [100 => 0, 200 => true],
                    Relation::EMBED => [100 => 0, 201 => true],
                ],
            ],
            [
                [
                    Relation::FIELD => [100],
                    Relation::ASSET => [100],
                    Relation::LINK => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                    Relation::EMBED => [
                        'locationIds' => [101],
                        'contentIds' => [100],
                    ],
                ],
                [
                    Relation::FIELD => [42 => [100 => 0]],
                    Relation::ASSET => [42 => [100 => 0]],
                    Relation::LINK => [100 => 0, 200 => true],
                    Relation::EMBED => [100 => 0, 201 => true],
                ],
            ],
        ];
    }

    /**
     * Test for the appendFieldRelations() method.
     *
     * @dataProvider providerForTestAppendRelations
     */
    public function testAppendFieldRelations(
        array $fieldRelations,
        array $expected
    ) {
        $locationHandler = $this->getPersistenceMock()->locationHandler();
        $relationProcessor = $this->getPartlyMockedRelationProcessor();
        $fieldValueMock = $this->getMockForAbstractClass(Value::class);
        $fieldTypeMock = $this->createMock(FieldType::class);

        $fieldTypeMock->expects($this->once())
            ->method('getRelations')
            ->with($this->equalTo($fieldValueMock))
            ->will($this->returnValue($fieldRelations));

        $this->assertLocationHandlerExpectation(
            $locationHandler,
            array_merge(
                $fieldRelations[Relation::LINK]['locationIds'] ?? [],
                $fieldRelations[Relation::EMBED]['locationIds'] ?? []
            )
        );

        $relations = [];
        $locationIdToContentIdMapping = [];

        $relationProcessor->appendFieldRelations(
            $relations,
            $locationIdToContentIdMapping,
            $fieldTypeMock,
            $fieldValueMock,
            42
        );

        $this->assertEquals($expected, $relations);
    }

    /**
     * Assert loading Locations to find Content id in {@link RelationProcessor::appendFieldRelations()} method.
     *
     * @param int[] $locationIds
     */
    protected function assertLocationHandlerExpectation(
        $locationHandlerMock,
        array $locationIds
    ) {
        if ($locationIds === []) {
            return;
        }

        $locationHandlerMock->expects($this->exactly(count($locationIds)))
            ->method('load')
            ->withConsecutive(...array_map(static function (int $locationId): array {
                return [$locationId];
            }, $locationIds))
            ->willReturnCallback(
                static function (int $locationId): Location {
                    return new Location(['contentId' => $locationId + 100]);
                }
            );
    }

    /**
     * Test for the appendFieldRelations() method.
     */
    public function testAppendFieldRelationsLocationMappingWorks()
    {
        $locationHandler = $this->getPersistenceMock()->locationHandler();
        $relationProcessor = $this->getPartlyMockedRelationProcessor();
        $fieldValueMock = $this->getMockForAbstractClass(Value::class);
        $fieldTypeMock = $this->createMock(FieldType::class);

        $fieldTypeMock->expects($this->once())
            ->method('getRelations')
            ->with($this->equalTo($fieldValueMock))
            ->will(
                $this->returnValue(
                    [
                        Relation::FIELD => [100],
                        Relation::ASSET => [100],
                        Relation::LINK => [
                            'locationIds' => [100],
                            'contentIds' => [100],
                        ],
                        Relation::EMBED => [
                            'locationIds' => [100],
                            'contentIds' => [100],
                        ],
                    ]
                )
            );

        $locationHandler->expects($this->once())
            ->method('load')
            ->with($this->equalTo(100))
            ->will(
                $this->returnValue(
                    new Location(
                        ['contentId' => 200]
                    )
                )
            );

        $relations = [];
        $locationIdToContentIdMapping = [];

        $relationProcessor->appendFieldRelations(
            $relations,
            $locationIdToContentIdMapping,
            $fieldTypeMock,
            $fieldValueMock,
            42
        );

        $this->assertEquals(
            [
                Relation::ASSET => [42 => [100 => 0]],
                Relation::FIELD => [42 => [100 => 0]],
                Relation::LINK => [100 => 0, 200 => true],
                Relation::EMBED => [100 => 0, 200 => true],
            ],
            $relations
        );
    }

    public function testAppendFieldRelationsLogsMissingLocations()
    {
        $fieldValueMock = $this->getMockForAbstractClass(Value::class);
        $fieldTypeMock = $this->createMock(FieldType::class);

        $locationId = 123465;
        $fieldDefinitionId = 42;

        $fieldTypeMock
            ->expects($this->once())
            ->method('getRelations')
            ->with($this->equalTo($fieldValueMock))
            ->will(
                $this->returnValue(
                    [
                        Relation::LINK => [
                            'locationIds' => [$locationId],
                        ],
                    ]
                )
            );

        $locationHandler = $this->getPersistenceMock()->locationHandler();
        $locationHandler
            ->expects($this->any())
            ->method('load')
            ->with($locationId)
            ->willThrowException($this->createMock(NotFoundException::class));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Invalid relation: destination location not found', [
                'fieldDefinitionId' => $fieldDefinitionId,
                'locationId' => $locationId,
            ]);

        $relations = [];
        $locationIdToContentIdMapping = [];

        $relationProcessor = $this->getPartlyMockedRelationProcessor();
        $relationProcessor->setLogger($logger);
        $relationProcessor->appendFieldRelations(
            $relations,
            $locationIdToContentIdMapping,
            $fieldTypeMock,
            $fieldValueMock,
            $fieldDefinitionId
        );
    }

    /**
     * Test for the processFieldRelations() method.
     */
    public function testProcessFieldRelationsNoChanges()
    {
        $relationProcessor = $this->getPartlyMockedRelationProcessor();
        $contentHandlerMock = $this->getPersistenceMockHandler('Content\\Handler');
        $contentTypeMock = $this->createMock(ContentType::class);

        $contentTypeMock->expects($this->exactly(2))
            ->method('getFieldDefinition')
            ->withConsecutive(['identifier42'], ['identifier43'])
            ->willReturnOnConsecutiveCalls(
                new FieldDefinition(['id' => 42]),
                new FieldDefinition(['id' => 43])
            );

        $contentHandlerMock->expects($this->never())->method('addRelation');
        $contentHandlerMock->expects($this->never())->method('removeRelation');

        $existingRelations = [
            $this->getStubbedRelation(1, Relation::COMMON, null, 10),
            $this->getStubbedRelation(2, Relation::EMBED, null, 11),
            $this->getStubbedRelation(3, Relation::LINK, null, 12),
            $this->getStubbedRelation(4, Relation::FIELD, 42, 13),
            // Legacy Storage cases - composite entries
            $this->getStubbedRelation(
                5,
                Relation::EMBED | Relation::COMMON,
                null,
                14
            ),
            $this->getStubbedRelation(
                6,
                Relation::LINK | Relation::COMMON,
                null,
                15
            ),
            $this->getStubbedRelation(
                7,
                Relation::EMBED | Relation::LINK,
                null,
                16
            ),
            $this->getStubbedRelation(
                8,
                Relation::EMBED | Relation::LINK | Relation::COMMON,
                null,
                17
            ),
            $this->getStubbedRelation(9, Relation::ASSET, 43, 18),
        ];
        $inputRelations = [
            Relation::EMBED => array_flip([11, 14, 16, 17]),
            Relation::LINK => array_flip([12, 15, 16, 17]),
            Relation::FIELD => [42 => array_flip([13])],
            Relation::ASSET => [43 => array_flip([18])],
        ];

        $relationProcessor->processFieldRelations(
            $inputRelations,
            24,
            2,
            $contentTypeMock,
            $existingRelations
        );
    }

    /**
     * Test for the processFieldRelations() method.
     */
    public function testProcessFieldRelationsAddsRelations()
    {
        $relationProcessor = $this->getPartlyMockedRelationProcessor();
        $contentHandlerMock = $this->getPersistenceMockHandler('Content\\Handler');
        $contentTypeMock = $this->createMock(ContentType::class);

        $existingRelations = [
            $this->getStubbedRelation(1, Relation::COMMON, null, 10),
            $this->getStubbedRelation(2, Relation::EMBED, null, 11),
            $this->getStubbedRelation(3, Relation::LINK, null, 12),
            // Legacy Storage cases - composite entries
            $this->getStubbedRelation(
                5,
                Relation::EMBED | Relation::COMMON,
                null,
                14
            ),
            $this->getStubbedRelation(
                6,
                Relation::LINK | Relation::COMMON,
                null,
                15
            ),
            $this->getStubbedRelation(
                7,
                Relation::EMBED | Relation::LINK,
                null,
                16
            ),
        ];
        $inputRelations = [
            Relation::EMBED => array_flip([11, 14, 16, 17]),
            Relation::LINK => array_flip([12, 15, 16, 17]),
            Relation::FIELD => [42 => array_flip([13])],
            Relation::ASSET => [44 => array_flip([18])],
        ];

        $contentTypeMock->expects($this->never())->method('getFieldDefinition');
        $contentHandlerMock->expects($this->never())->method('removeRelation');

        $contentHandlerMock->expects($this->exactly(4))
            ->method('addRelation')
            ->withConsecutive(
                [
                    new CreateStruct(
                        [
                            'sourceContentId' => 24,
                            'sourceContentVersionNo' => 2,
                            'sourceFieldDefinitionId' => null,
                            'destinationContentId' => 17,
                            'type' => Relation::EMBED,
                        ]
                    ),
                ],
                [
                    new CreateStruct(
                        [
                            'sourceContentId' => 24,
                            'sourceContentVersionNo' => 2,
                            'sourceFieldDefinitionId' => null,
                            'destinationContentId' => 17,
                            'type' => Relation::LINK,
                        ]
                    ),
                ],
                [
                    new CreateStruct(
                        [
                            'sourceContentId' => 24,
                            'sourceContentVersionNo' => 2,
                            'sourceFieldDefinitionId' => 42,
                            'destinationContentId' => 13,
                            'type' => Relation::FIELD,
                        ]
                    ),
                ],
                [
                    new CreateStruct(
                        [
                            'sourceContentId' => 24,
                            'sourceContentVersionNo' => 2,
                            'sourceFieldDefinitionId' => 44,
                            'destinationContentId' => 18,
                            'type' => Relation::ASSET,
                        ]
                    ),
                ]
            );

        $relationProcessor->processFieldRelations(
            $inputRelations,
            24,
            2,
            $contentTypeMock,
            $existingRelations
        );
    }

    /**
     * Test for the processFieldRelations() method.
     */
    public function testProcessFieldRelationsRemovesRelations()
    {
        $relationProcessor = $this->getPartlyMockedRelationProcessor();
        $contentHandlerMock = $this->getPersistenceMockHandler('Content\\Handler');
        $contentTypeMock = $this->createMock(ContentType::class);

        $existingRelations = [
            $this->getStubbedRelation(1, Relation::COMMON, null, 10),
            $this->getStubbedRelation(2, Relation::EMBED, null, 11),
            $this->getStubbedRelation(3, Relation::LINK, null, 12),
            $this->getStubbedRelation(4, Relation::FIELD, 42, 13),
            // Legacy Storage cases - composite entries
            $this->getStubbedRelation(
                5,
                Relation::EMBED | Relation::COMMON,
                null,
                14
            ),
            $this->getStubbedRelation(
                6,
                Relation::LINK | Relation::COMMON,
                null,
                15
            ),
            $this->getStubbedRelation(
                7,
                Relation::EMBED | Relation::LINK,
                null,
                16
            ),
            $this->getStubbedRelation(
                8,
                Relation::EMBED | Relation::LINK | Relation::COMMON,
                null,
                17
            ),
            $this->getStubbedRelation(9, Relation::FIELD, 44, 18),
        ];
        $inputRelations = [
            Relation::EMBED => array_flip([11, 14, 17]),
            Relation::LINK => array_flip([12, 15, 17]),
        ];

        $contentHandlerMock->expects($this->never())->method('addRelation');

        $contentTypeMock->expects(self::exactly(2))
            ->method('getFieldDefinition')
            ->withConsecutive(['identifier42'], ['identifier44'])
            ->willReturnOnConsecutiveCalls(
                new FieldDefinition(['id' => 42]),
                new FieldDefinition(['id' => 44])
            );

        $contentHandlerMock->expects(self::exactly(4))
            ->method('removeRelation')
            ->withConsecutive(
                [7, Relation::EMBED, 16],
                [7, Relation::LINK, 16],
                [4, Relation::FIELD, 13],
                [9, Relation::FIELD]
            );

        $relationProcessor->processFieldRelations(
            $inputRelations,
            24,
            2,
            $contentTypeMock,
            $existingRelations
        );
    }

    /**
     * Test for the processFieldRelations() method.
     */
    public function testProcessFieldRelationsWhenRelationFieldNoLongerExists()
    {
        $existingRelations = [
            $this->getStubbedRelation(2, Relation::FIELD, 43, 17),
            $this->getStubbedRelation(2, Relation::ASSET, 44, 18),
        ];

        $contentTypeMock = $this->createMock(ContentType::class);
        $contentTypeMock
            ->expects($this->exactly(2))
            ->method('getFieldDefinition')
            ->withConsecutive(['identifier43'], ['identifier44'])
            ->willReturn(null);

        $relationProcessor = $this->getPartlyMockedRelationProcessor();
        $relationProcessor->processFieldRelations([], 24, 2, $contentTypeMock, $existingRelations);
    }

    protected function getStubbedRelation(
        $id,
        $type,
        $fieldDefinitionId,
        $contentId
    ) {
        return new RelationValue(
            [
                'id' => $id,
                'type' => $type,
                'destinationContentInfo' => new ContentInfo(['id' => $contentId]),
                'sourceFieldDefinitionIdentifier' => $fieldDefinitionId ?
                    'identifier' . $fieldDefinitionId :
                    null,
            ]
        );
    }

    /**
     * Returns the content service to test with $methods mocked.
     *
     * Injected Repository comes from {@see getRepositoryMock()}
     *
     * @param string[] $methods
     *
     * @return RelationProcessor|MockObject
     */
    protected function getPartlyMockedRelationProcessor(?array $methods = null)
    {
        return $this->getMockBuilder(RelationProcessor::class)
            ->setMethods($methods)
            ->setConstructorArgs(
                [
                    $this->getPersistenceMock(),
                ]
            )
            ->getMock();
    }

    /**
     * @return MockObject
     */
    protected function getFieldTypeServiceMock()
    {
        return $this->createMock(FieldTypeService::class);
    }
}

class_alias(RelationProcessorTest::class, 'eZ\Publish\Core\Repository\Tests\Service\Mock\RelationProcessorTest');
