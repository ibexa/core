<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\Handler as SPIContentHandler;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\RelationList\Type as RelationList;
use Ibexa\Core\FieldType\RelationList\Value;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Repository\Validator\TargetContentValidatorInterface;

class RelationListTest extends FieldTypeTestCase
{
    private const DESTINATION_CONTENT_ID_14 = 14;
    private const DESTINATION_CONTENT_ID_22 = 22;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Handler */
    private $contentHandler;

    /** @var \Ibexa\Core\Repository\Validator\TargetContentValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $targetContentValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->targetContentValidator = $this->createMock(TargetContentValidatorInterface::class);

        $versionInfo14 = new VersionInfo([
            'versionNo' => 1,
            'names' => [
                'en_GB' => 'name_14_en_GB',
                'de_DE' => 'Name_14_de_DE',
            ],
        ]);
        $versionInfo22 = new VersionInfo([
            'versionNo' => 1,
            'names' => [
                'en_GB' => 'name_22_en_GB',
                'de_DE' => 'Name_22_de_DE',
            ],
        ]);
        $currentVersionNoFor14 = 44;
        $destinationContentInfo14 = $this->createMock(ContentInfo::class);
        $destinationContentInfo14
            ->method('__get')
            ->willReturnMap([
                ['currentVersionNo', $currentVersionNoFor14],
                ['mainLanguageCode', 'en_GB'],
            ]);
        $currentVersionNoFor22 = 22;
        $destinationContentInfo22 = $this->createMock(ContentInfo::class);
        $destinationContentInfo22
            ->method('__get')
            ->willReturnMap([
                ['currentVersionNo', $currentVersionNoFor22],
                ['mainLanguageCode', 'en_GB'],
            ]);

        $this->contentHandler = $this->createMock(SPIContentHandler::class);
        $this->contentHandler
            ->method('loadContentInfo')
            ->willReturnMap([
                [self::DESTINATION_CONTENT_ID_14, $destinationContentInfo14],
                [self::DESTINATION_CONTENT_ID_22, $destinationContentInfo22],
            ]);

        $this->contentHandler
            ->method('loadVersionInfo')
            ->willReturnMap([
                [self::DESTINATION_CONTENT_ID_14, $currentVersionNoFor14, $versionInfo14],
                [self::DESTINATION_CONTENT_ID_22, $currentVersionNoFor22, $versionInfo22],
            ]);
    }

    protected function createFieldTypeUnderTest(): RelationList
    {
        $fieldType = new RelationList(
            $this->contentHandler,
            $this->targetContentValidator
        );
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [
            'RelationListValueValidator' => [
                'selectionLimit' => [
                    'type' => 'int',
                    'default' => 0,
                ],
            ],
        ];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [
            'selectionMethod' => [
                'type' => 'int',
                'default' => RelationList::SELECTION_BROWSE,
            ],
            'selectionDefaultLocation' => [
                'type' => 'string',
                'default' => null,
            ],
            'rootDefaultLocation' => [
                'type' => 'bool',
                'default' => false,
            ],
            'selectionContentTypes' => [
                'type' => 'array',
                'default' => [],
            ],
        ];
    }

    protected function getEmptyValueExpectation(): Value
    {
        return new Value();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                true,
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'empty value object' => [
            new Value(),
            new Value(),
        ];

        yield 'single content id' => [
            23,
            new Value([23]),
        ];

        yield 'content info object' => [
            new ContentInfo(['id' => 23]),
            new Value([23]),
        ];

        yield 'array of content ids' => [
            [23, 42],
            new Value([23, 42]),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new Value([23, 42]),
                ['destinationContentIds' => [23, 42]],
            ],
            [
                new Value(),
                ['destinationContentIds' => []],
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                ['destinationContentIds' => [23, 42]],
                new Value([23, 42]),
            ],
            [
                ['destinationContentIds' => []],
                new Value(),
            ],
        ];
    }

    public function provideValidFieldSettings(): iterable
    {
        return [
            [
                [
                    'selectionMethod' => RelationList::SELECTION_BROWSE,
                    'selectionDefaultLocation' => 23,
                ],
            ],
            [
                [
                    'selectionMethod' => RelationList::SELECTION_DROPDOWN,
                    'selectionDefaultLocation' => 'foo',
                ],
            ],
            [
                [
                    'selectionMethod' => RelationList::SELECTION_DROPDOWN,
                    'selectionDefaultLocation' => 'foo',
                    'selectionContentTypes' => [1, 2, 3],
                ],
            ],
            [
                [
                    'selectionMethod' => RelationList::SELECTION_LIST_WITH_RADIO_BUTTONS,
                    'selectionDefaultLocation' => 'foo',
                    'selectionContentTypes' => [1, 2, 3],
                ],
            ],
            [
                [
                    'selectionMethod' => RelationList::SELECTION_LIST_WITH_CHECKBOXES,
                    'selectionDefaultLocation' => 'foo',
                    'selectionContentTypes' => [1, 2, 3],
                ],
            ],
            [
                [
                    'selectionMethod' => RelationList::SELECTION_MULTIPLE_SELECTION_LIST,
                    'selectionDefaultLocation' => 'foo',
                    'selectionContentTypes' => [1, 2, 3],
                ],
            ],
            [
                [
                    'selectionMethod' => RelationList::SELECTION_TEMPLATE_BASED_MULTIPLE,
                    'selectionDefaultLocation' => 'foo',
                    'selectionContentTypes' => [1, 2, 3],
                ],
            ],
            [
                [
                    'selectionMethod' => RelationList::SELECTION_TEMPLATE_BASED_SINGLE,
                    'selectionDefaultLocation' => 'foo',
                    'selectionContentTypes' => [1, 2, 3],
                ],
            ],
        ];
    }

    public function provideInValidFieldSettings(): array
    {
        return [
            [
                // Invalid value for 'selectionMethod'
                [
                    'selectionMethod' => true,
                    'selectionDefaultLocation' => 23,
                ],
            ],
            [
                // Invalid value for 'selectionDefaultLocation'
                [
                    'selectionMethod' => RelationList::SELECTION_DROPDOWN,
                    'selectionDefaultLocation' => [],
                ],
            ],
            [
                // Invalid value for 'selectionContentTypes'
                [
                    'selectionMethod' => RelationList::SELECTION_DROPDOWN,
                    'selectionDefaultLocation' => 23,
                    'selectionContentTypes' => true,
                ],
            ],
            [
                // Invalid value for 'selectionMethod'
                [
                    'selectionMethod' => 9,
                    'selectionDefaultLocation' => 23,
                    'selectionContentTypes' => true,
                ],
            ],
        ];
    }

    public function provideValidValidatorConfiguration(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'RelationListValueValidator' => [
                        'selectionLimit' => 0,
                    ],
                ],
            ],
            [
                [
                    'RelationListValueValidator' => [
                        'selectionLimit' => 14,
                    ],
                ],
            ],
        ];
    }

    public function provideInvalidValidatorConfiguration(): array
    {
        return [
            [
                [
                    'NonExistentValidator' => [],
                ],
            ],
            [
                [
                    'RelationListValueValidator' => [
                        'nonExistentValue' => 14,
                    ],
                ],
            ],
            [
                [
                    'RelationListValueValidator' => [
                        'selectionLimit' => 'foo',
                    ],
                ],
            ],
            [
                [
                    'RelationListValueValidator' => [
                        'selectionLimit' => -10,
                    ],
                ],
            ],
        ];
    }

    public function provideValidDataForValidate(): iterable
    {
        yield 'unlimited selection limit' => [
            [
                'validatorConfiguration' => [
                    'RelationListValueValidator' => [
                        'selectionLimit' => 0,
                    ],
                ],
            ],
            new Value([5, 6, 7]),
        ];

        yield 'single selection limit' => [
            [
                'validatorConfiguration' => [
                    'RelationListValueValidator' => [
                        'selectionLimit' => 1,
                    ],
                ],
            ],
            new Value([5]),
        ];

        yield 'selection within limit' => [
            [
                'validatorConfiguration' => [
                    'RelationListValueValidator' => [
                        'selectionLimit' => 3,
                    ],
                ],
            ],
            new Value([5, 6]),
        ];

        yield 'empty selection' => [
            [
                'validatorConfiguration' => [
                    'RelationListValueValidator' => [
                        'selectionLimit' => 3,
                    ],
                ],
            ],
            new Value([]),
        ];
    }

    public function provideInvalidDataForValidate(): iterable
    {
        yield 'selection exceeds limit' => [
            [
                'validatorConfiguration' => [
                    'RelationListValueValidator' => [
                        'selectionLimit' => 3,
                    ],
                ],
            ],
            new Value([1, 2, 3, 4]),
            [
                new ValidationError(
                    'The selected content items number cannot be higher than %limit%.',
                    null,
                    [
                        '%limit%' => 3,
                    ],
                    'destinationContentIds'
                ),
            ],
        ];
    }

    public function testValidateNotExistingContentRelations(): void
    {
        $invalidDestinationContentId = (int) 'invalid';
        $invalidDestinationContentId2 = (int) 'invalid-second';

        $this->targetContentValidator
            ->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive([$invalidDestinationContentId], [$invalidDestinationContentId2])
            ->willReturnOnConsecutiveCalls(
                $this->generateValidationError($invalidDestinationContentId),
                $this->generateValidationError($invalidDestinationContentId2)
            );

        $validationErrors = $this->doValidate([], new Value([$invalidDestinationContentId, $invalidDestinationContentId2]));

        self::assertIsArray($validationErrors);
        self::assertCount(2, $validationErrors);
    }

    public function testValidateInvalidContentType(): void
    {
        $destinationContentId = 12;
        $destinationContentId2 = 13;
        $allowedContentTypes = ['article', 'folder'];

        $this->targetContentValidator
            ->method('validate')
            ->withConsecutive(
                [$destinationContentId, $allowedContentTypes],
                [$destinationContentId2, $allowedContentTypes]
            )
            ->willReturnOnConsecutiveCalls(
                $this->generateContentTypeValidationError('test'),
                $this->generateContentTypeValidationError('test')
            );

        $validationErrors = $this->doValidate(
            ['fieldSettings' => ['selectionContentTypes' => $allowedContentTypes]],
            new Value([$destinationContentId, $destinationContentId2])
        );

        self::assertIsArray($validationErrors);
        self::assertCount(2, $validationErrors);
    }

    private function generateValidationError(string $contentId): ValidationError
    {
        return new ValidationError(
            'Content with identifier %contentId% is not a valid relation target',
            null,
            [
                '%contentId%' => $contentId,
            ],
            'targetContentId'
        );
    }

    private function generateContentTypeValidationError(string $contentTypeIdentifier): ValidationError
    {
        return new ValidationError(
            'Content type %contentTypeIdentifier% is not a valid relation target',
            null,
            [
                '%contentTypeIdentifier%' => $contentTypeIdentifier,
            ],
            'targetContentId'
        );
    }

    public function testGetRelations(): void
    {
        $ft = $this->createFieldTypeUnderTest();
        self::assertEquals(
            [
                RelationType::FIELD->value => [70, 72],
            ],
            $ft->getRelations($ft->acceptValue([70, 72]))
        );
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'ibexa_object_relation_list';
    }

    /**
     * @dataProvider provideDataForGetName
     */
    public function testGetName(
        SPIValue $value,
        string $expected,
        array $fieldSettings = [],
        string $languageCode = 'en_GB'
    ): void {
        $fieldDefinitionMock = $this->getFieldDefinitionMock($fieldSettings);

        $name = $this->getFieldTypeUnderTest()->getName($value, $fieldDefinitionMock, $languageCode);

        self::assertSame($expected, $name);
    }

    public function provideDataForGetName(): array
    {
        return [
            [$this->getEmptyValueExpectation(), '', [], 'en_GB'],
            [new Value([self::DESTINATION_CONTENT_ID_14, self::DESTINATION_CONTENT_ID_22]), 'name_14_en_GB name_22_en_GB', [], 'en_GB'],
            [new Value([self::DESTINATION_CONTENT_ID_14, self::DESTINATION_CONTENT_ID_22]), 'Name_14_de_DE Name_22_de_DE', [], 'de_DE'],
        ];
    }
}
