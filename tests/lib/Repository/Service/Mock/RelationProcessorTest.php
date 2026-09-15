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
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\FieldType\Value;
use Ibexa\Core\Repository\FieldTypeService;
use Ibexa\Core\Repository\Helper\RelationProcessor;
use Ibexa\Core\Repository\Values\Content\Relation as RelationValue;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use Psr\Log\LoggerInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Core\Repository\Helper\RelationProcessor::class)]
class RelationProcessorTest extends BaseServiceMockTest
{
    public static function providerForTestAppendRelations()
    {
        return [
            [
                [RelationType::FIELD->value => [100]],
                [RelationType::FIELD->value => [42 => [100 => 0]]],
            ],
            [
                [RelationType::LINK->value => ['contentIds' => [100]]],
                [RelationType::LINK->value => [100 => 0]],
            ],
            [
                [RelationType::EMBED->value => ['contentIds' => [100]]],
                [RelationType::EMBED->value => [100 => 0]],
            ],
            [
                [RelationType::ASSET->value => [100]],
                [RelationType::ASSET->value => [42 => [100 => 0]]],
            ],
            [
                [
                    RelationType::FIELD->value => [100],
                    RelationType::LINK->value => ['contentIds' => [100]],
                    RelationType::EMBED->value => ['contentIds' => [100]],
                ],
                [
                    RelationType::FIELD->value => [42 => [100 => 0]],
                    RelationType::LINK->value => [100 => 0],
                    RelationType::EMBED->value => [100 => 0],
                ],
            ],
            [
                [RelationType::LINK->value => ['locationIds' => [100]]],
                [RelationType::LINK->value => [200 => true]],
            ],
            [
                [
                    RelationType::LINK->value => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                ],
                [RelationType::LINK->value => [100 => 0, 200 => true]],
            ],
            [
                [RelationType::EMBED->value => ['locationIds' => [100]]],
                [RelationType::EMBED->value => [200 => true]],
            ],
            [
                [
                    RelationType::EMBED->value => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                ],
                [RelationType::EMBED->value => [100 => 0, 200 => true]],
            ],
            [
                [
                    RelationType::LINK->value => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                    RelationType::EMBED->value => [
                        'locationIds' => [101],
                        'contentIds' => [100],
                    ],
                ],
                [
                    RelationType::LINK->value => [100 => 0, 200 => true],
                    RelationType::EMBED->value => [100 => 0, 201 => true],
                ],
            ],
            [
                [
                    RelationType::FIELD->value => [100],
                    RelationType::LINK->value => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                    RelationType::EMBED->value => [
                        'locationIds' => [101],
                        'contentIds' => [100],
                    ],
                ],
                [
                    RelationType::FIELD->value => [42 => [100 => 0]],
                    RelationType::LINK->value => [100 => 0, 200 => true],
                    RelationType::EMBED->value => [100 => 0, 201 => true],
                ],
            ],
            [
                [
                    RelationType::ASSET->value => [100],
                    RelationType::LINK->value => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                    RelationType::EMBED->value => [
                        'locationIds' => [101],
                        'contentIds' => [100],
                    ],
                ],
                [
                    RelationType::ASSET->value => [42 => [100 => 0]],
                    RelationType::LINK->value => [100 => 0, 200 => true],
                    RelationType::EMBED->value => [100 => 0, 201 => true],
                ],
            ],
            [
                [
                    RelationType::FIELD->value => [100],
                    RelationType::ASSET->value => [100],
                    RelationType::LINK->value => [
                        'locationIds' => [100],
                        'contentIds' => [100],
                    ],
                    RelationType::EMBED->value => [
                        'locationIds' => [101],
                        'contentIds' => [100],
                    ],
                ],
                [
                    RelationType::FIELD->value => [42 => [100 => 0]],
                    RelationType::ASSET->value => [42 => [100 => 0]],
                    RelationType::LINK->value => [100 => 0, 200 => true],
                    RelationType::EMBED->value => [100 => 0, 201 => true],
                ],
            ],
        ];
    }

    /**
     * Test for the appendFieldRelations() method.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerForTestAppendRelations')]
    public function testAppendFieldRelations(array $fieldRelations, array $expected)
    {
        $locationHandler = $this->getPersistenceMock()->locationHandler();
        $relationProcessor = $this->getPartlyMockedRelationProcessor();
        $fieldValueMock = $this->getMockForAbstractClass(Value::class);
        $fieldTypeMock = $this->createMock(FieldType::class);

        $fieldTypeMock->expects(self::once())
            ->method('getRelations')
            ->with(self::equalTo($fieldValueMock))
            ->will(self::returnValue($fieldRelations));

        $expectedLocationIds = [];
        $this->assertLocationHandlerExpectation(
            $fieldRelations,
            RelationType::LINK->value,
            $expectedLocationIds
        );
        $this->assertLocationHandlerExpectation(
            $fieldRelations,
            RelationType::EMBED->value,
            $expectedLocationIds
        );

        if ($expectedLocationIds !== []) {
            $loadMatcher = self::exactly(count($expectedLocationIds));
            $locationHandler->expects($loadMatcher)
                ->method('load')
                ->willReturnCallback(static function ($locationId) use ($loadMatcher, $expectedLocationIds): Location {
                    $expectedLocationId = $expectedLocationIds[$loadMatcher->numberOfInvocations() - 1];
                    self::assertSame($expectedLocationId, $locationId);

                    return new Location(['contentId' => $expectedLocationId + 100]);
                });
        }

        $relations = [];
        $locationIdToContentIdMapping = [];

        $relationProcessor->appendFieldRelations(
            $relations,
            $locationIdToContentIdMapping,
            $fieldTypeMock,
            $fieldValueMock,
            42
        );

        self::assertEquals($expected, $relations);
    }

    /**
     * Assert loading Locations to find Content id in {@link RelationProcessor::appendFieldRelations()} method.
     */
    protected function assertLocationHandlerExpectation($fieldRelations, $type, array &$expectedLocationIds)
    {
        if (isset($fieldRelations[$type]['locationIds'])) {
            foreach ($fieldRelations[$type]['locationIds'] as $locationId) {
                $expectedLocationIds[] = $locationId;
            }
        }
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

        $fieldTypeMock->expects(self::once())
            ->method('getRelations')
            ->with(self::equalTo($fieldValueMock))
            ->will(
                self::returnValue(
                    [
                        RelationType::FIELD->value => [100],
                        RelationType::ASSET->value => [100],
                        RelationType::LINK->value => [
                            'locationIds' => [100],
                            'contentIds' => [100],
                        ],
                        RelationType::EMBED->value => [
                            'locationIds' => [100],
                            'contentIds' => [100],
                        ],
                    ]
                )
            );

        $locationHandler->expects(self::once())
            ->method('load')
            ->with(self::equalTo(100))
            ->will(
                self::returnValue(
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

        self::assertEquals(
            [
                RelationType::ASSET->value => [42 => [100 => 0]],
                RelationType::FIELD->value => [42 => [100 => 0]],
                RelationType::LINK->value => [100 => 0, 200 => true],
                RelationType::EMBED->value => [100 => 0, 200 => true],
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
            ->expects(self::once())
            ->method('getRelations')
            ->with(self::equalTo($fieldValueMock))
            ->will(
                self::returnValue(
                    [
                        RelationType::LINK->value => [
                            'locationIds' => [$locationId],
                        ],
                    ]
                )
            );

        $locationHandler = $this->getPersistenceMock()->locationHandler();
        $locationHandler
            ->expects(self::any())
            ->method('load')
            ->with($locationId)
            ->willThrowException($this->createMock(NotFoundException::class));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
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

        $getFieldDefinitionMatcher = self::exactly(2);
        $contentTypeMock->expects($getFieldDefinitionMatcher)
            ->method('getFieldDefinition')
            ->willReturnCallback(static function ($identifier) use ($getFieldDefinitionMatcher): FieldDefinition {
                if ($getFieldDefinitionMatcher->numberOfInvocations() === 1) {
                    self::assertSame('identifier42', $identifier);

                    return new FieldDefinition(['id' => 42]);
                }

                self::assertSame('identifier43', $identifier);

                return new FieldDefinition(['id' => 43]);
            });

        $contentHandlerMock->expects(self::never())->method('addRelation');
        $contentHandlerMock->expects(self::never())->method('removeRelation');

        $existingRelations = [
            $this->getStubbedRelation(1, RelationType::COMMON->value, null, 10),
            $this->getStubbedRelation(2, RelationType::EMBED->value, null, 11),
            $this->getStubbedRelation(3, RelationType::LINK->value, null, 12),
            $this->getStubbedRelation(4, RelationType::FIELD->value, 42, 13),
            // Legacy Storage cases - composite entries
            $this->getStubbedRelation(
                5,
                RelationType::EMBED->value | RelationType::COMMON->value,
                null,
                14
            ),
            $this->getStubbedRelation(
                6,
                RelationType::LINK->value | RelationType::COMMON->value,
                null,
                15
            ),
            $this->getStubbedRelation(
                7,
                RelationType::EMBED->value | RelationType::LINK->value,
                null,
                16
            ),
            $this->getStubbedRelation(
                8,
                RelationType::EMBED->value | RelationType::LINK->value | RelationType::COMMON->value,
                null,
                17
            ),
            $this->getStubbedRelation(9, RelationType::ASSET->value, 43, 18),
        ];
        $inputRelations = [
            RelationType::EMBED->value => array_flip([11, 14, 16, 17]),
            RelationType::LINK->value => array_flip([12, 15, 16, 17]),
            RelationType::FIELD->value => [42 => array_flip([13])],
            RelationType::ASSET->value => [43 => array_flip([18])],
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
            $this->getStubbedRelation(1, RelationType::COMMON->value, null, 10),
            $this->getStubbedRelation(2, RelationType::EMBED->value, null, 11),
            $this->getStubbedRelation(3, RelationType::LINK->value, null, 12),
            // Legacy Storage cases - composite entries
            $this->getStubbedRelation(
                5,
                RelationType::EMBED->value | RelationType::COMMON->value,
                null,
                14
            ),
            $this->getStubbedRelation(
                6,
                RelationType::LINK->value | RelationType::COMMON->value,
                null,
                15
            ),
            $this->getStubbedRelation(
                7,
                RelationType::EMBED->value | RelationType::LINK->value,
                null,
                16
            ),
        ];
        $inputRelations = [
            RelationType::EMBED->value => array_flip([11, 14, 16, 17]),
            RelationType::LINK->value => array_flip([12, 15, 16, 17]),
            RelationType::FIELD->value => [42 => array_flip([13])],
            RelationType::ASSET->value => [44 => array_flip([18])],
        ];

        $contentTypeMock->expects(self::never())->method('getFieldDefinition');
        $contentHandlerMock->expects(self::never())->method('removeRelation');

        $expectedAddRelationCreateStructs = [
            new CreateStruct(
                [
                    'sourceContentId' => 24,
                    'sourceContentVersionNo' => 2,
                    'sourceFieldDefinitionId' => null,
                    'destinationContentId' => 17,
                    'type' => RelationType::EMBED->value,
                ]
            ),
            new CreateStruct(
                [
                    'sourceContentId' => 24,
                    'sourceContentVersionNo' => 2,
                    'sourceFieldDefinitionId' => null,
                    'destinationContentId' => 17,
                    'type' => RelationType::LINK->value,
                ]
            ),
            new CreateStruct(
                [
                    'sourceContentId' => 24,
                    'sourceContentVersionNo' => 2,
                    'sourceFieldDefinitionId' => 42,
                    'destinationContentId' => 13,
                    'type' => RelationType::FIELD->value,
                ]
            ),
            new CreateStruct(
                [
                    'sourceContentId' => 24,
                    'sourceContentVersionNo' => 2,
                    'sourceFieldDefinitionId' => 44,
                    'destinationContentId' => 18,
                    'type' => RelationType::ASSET->value,
                ]
            ),
        ];
        $addRelationMatcher = self::exactly(count($expectedAddRelationCreateStructs));
        $contentHandlerMock->expects($addRelationMatcher)
            ->method('addRelation')
            ->willReturnCallback(static function ($createStruct) use ($addRelationMatcher, $expectedAddRelationCreateStructs): void {
                self::assertEquals(
                    $expectedAddRelationCreateStructs[$addRelationMatcher->numberOfInvocations() - 1],
                    $createStruct
                );
            });

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
            $this->getStubbedRelation(1, RelationType::COMMON->value, null, 10),
            $this->getStubbedRelation(2, RelationType::EMBED->value, null, 11),
            $this->getStubbedRelation(3, RelationType::LINK->value, null, 12),
            $this->getStubbedRelation(4, RelationType::FIELD->value, 42, 13),
            // Legacy Storage cases - composite entries
            $this->getStubbedRelation(
                5,
                RelationType::EMBED->value | RelationType::COMMON->value,
                null,
                14
            ),
            $this->getStubbedRelation(
                6,
                RelationType::LINK->value | RelationType::COMMON->value,
                null,
                15
            ),
            $this->getStubbedRelation(
                7,
                RelationType::EMBED->value | RelationType::LINK->value,
                null,
                16
            ),
            $this->getStubbedRelation(
                8,
                RelationType::EMBED->value | RelationType::LINK->value | RelationType::COMMON->value,
                null,
                17
            ),
            $this->getStubbedRelation(9, RelationType::FIELD->value, 44, 18),
        ];
        $inputRelations = [
            RelationType::EMBED->value => array_flip([11, 14, 17]),
            RelationType::LINK->value => array_flip([12, 15, 17]),
        ];

        $contentHandlerMock->expects(self::never())->method('addRelation');

        $getFieldDefinitionMatcher = self::exactly(2);
        $contentTypeMock->expects($getFieldDefinitionMatcher)
            ->method('getFieldDefinition')
            ->willReturnCallback(static function ($identifier) use ($getFieldDefinitionMatcher): FieldDefinition {
                if ($getFieldDefinitionMatcher->numberOfInvocations() === 1) {
                    self::assertSame('identifier42', $identifier);

                    return new FieldDefinition(['id' => 42]);
                }

                self::assertSame('identifier44', $identifier);

                return new FieldDefinition(['id' => 44]);
            });

        $expectedRemoveRelationCalls = [
            [7, RelationType::EMBED->value, 16],
            [7, RelationType::LINK->value, 16],
            [4, RelationType::FIELD->value, 13],
            [9, RelationType::FIELD->value, 18],
        ];
        $removeRelationMatcher = self::exactly(count($expectedRemoveRelationCalls));
        $contentHandlerMock->expects($removeRelationMatcher)
            ->method('removeRelation')
            ->willReturnCallback(static function ($relationId, $type, $destinationContentId = null) use ($removeRelationMatcher, $expectedRemoveRelationCalls): void {
                [$expectedRelationId, $expectedType, $expectedDestinationContentId] = $expectedRemoveRelationCalls[$removeRelationMatcher->numberOfInvocations() - 1];
                self::assertSame($expectedRelationId, $relationId);
                self::assertSame($expectedType, $type);
                self::assertSame($expectedDestinationContentId, $destinationContentId);
            });

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
            $this->getStubbedRelation(2, RelationType::FIELD->value, 43, 17),
            $this->getStubbedRelation(2, RelationType::ASSET->value, 44, 18),
        ];

        $contentTypeMock = $this->createMock(ContentType::class);
        $getFieldDefinitionMatcher = self::exactly(2);
        $contentTypeMock
            ->expects($getFieldDefinitionMatcher)
            ->method('getFieldDefinition')
            ->willReturnCallback(static function ($identifier) use ($getFieldDefinitionMatcher): ?FieldDefinition {
                if ($getFieldDefinitionMatcher->numberOfInvocations() === 1) {
                    self::assertSame('identifier43', $identifier);
                } else {
                    self::assertSame('identifier44', $identifier);
                }

                return null;
            });

        $relationProcessor = $this->getPartlyMockedRelationProcessor();
        $relationProcessor->processFieldRelations([], 24, 2, $contentTypeMock, $existingRelations);
    }

    protected function getStubbedRelation($id, $type, $fieldDefinitionId, $contentId)
    {
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
     * @return \Ibexa\Core\Repository\Helper\RelationProcessor|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPartlyMockedRelationProcessor(?array $methods = null)
    {
        return $this->getMockBuilder(RelationProcessor::class)
            ->setConstructorArgs(
                [
                    $this->getPersistenceMock(),
                ]
            )
            ->onlyMethods(array_values($methods ?? []))
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFieldTypeServiceMock()
    {
        return $this->createMock(FieldTypeService::class);
    }
}
