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

class RelationListTest extends FieldTypeTest
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

    /**
     * Returns the field type under test.
     *
     * This method is used by all test cases to retrieve the field type under
     * test. Just create the FieldType instance using mocks from the provided
     * get*Mock() methods and/or custom get*Mock() implementations. You MUST
     * NOT take care for test case wide caching of the field type, just return
     * a new instance from this method!
     */
    protected function createFieldTypeUnderTest(): RelationList
    {
        $fieldType = new RelationList(
            $this->contentHandler,
            $this->targetContentValidator
        );
        $fieldType->setTransformationProcessor($this->getTransformationProcessorMock());

        return $fieldType;
    }

    /**
     * Returns the validator configuration schema expected from the field type.
     *
     * @return array
     */
    protected function getValidatorConfigurationSchemaExpectation()
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

    /**
     * Returns the settings schema expected from the field type.
     *
     * @return array
     */
    protected function getSettingsSchemaExpectation()
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

    /**
     * Returns the empty value expected from the field type.
     *
     * @return \Ibexa\Core\FieldType\RelationList\Value
     */
    protected function getEmptyValueExpectation()
    {
        // @todo FIXME: Is this correct?
        return new Value();
    }

    public function provideInvalidInputForAcceptValue()
    {
        return [
            [
                true,
                InvalidArgumentException::class,
            ],
        ];
    }

    /**
     * Data provider for valid input to acceptValue().
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to acceptValue(), 2. The expected return value from acceptValue().
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          null,
     *          null
     *      ),
     *      array(
     *          __FILE__,
     *          new BinaryFileValue( array(
     *              'path' => __FILE__,
     *              'fileName' => basename( __FILE__ ),
     *              'fileSize' => filesize( __FILE__ ),
     *              'downloadCount' => 0,
     *              'mimeType' => 'text/plain',
     *          ) )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideValidInputForAcceptValue()
    {
        return [
            [
                new Value(),
                new Value(),
            ],
            [
                23,
                new Value([23]),
            ],
            [
                new ContentInfo(['id' => 23]),
                new Value([23]),
            ],
            [
                [23, 42],
                new Value([23, 42]),
            ],
        ];
    }

    /**
     * Provide input for the toHash() method.
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to toHash(), 2. The expected return value from toHash().
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          null,
     *          null
     *      ),
     *      array(
     *          new BinaryFileValue( array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ) ),
     *          array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInputForToHash()
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

    /**
     * Provide input to fromHash() method.
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to fromHash(), 2. The expected return value from fromHash().
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          null,
     *          null
     *      ),
     *      array(
     *          array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ),
     *          new BinaryFileValue( array(
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ) )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInputForFromHash()
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

    /**
     * Provide data sets with field settings which are considered valid by the
     * {@link validateFieldSettings()} method.
     *
     * Returns an array of data provider sets with a single argument: A valid
     * set of field settings.
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          array(),
     *      ),
     *      array(
     *          array( 'rows' => 2 )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideValidFieldSettings()
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

    /**
     * Provide data sets with field settings which are considered invalid by the
     * {@link validateFieldSettings()} method. The method must return a
     * non-empty array of validation error when receiving such field settings.
     *
     * ATTENTION: This is a default implementation, which must be overwritten
     * if a FieldType supports field settings!
     *
     * Returns an array of data provider sets with a single argument: A valid
     * set of field settings.
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          true,
     *      ),
     *      array(
     *          array( 'nonExistentKey' => 2 )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInValidFieldSettings()
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

    /**
     * Provide data sets with validator configurations which are considered
     * valid by the {@link validateValidatorConfiguration()} method.
     *
     * Returns an array of data provider sets with a single argument: A valid
     * set of validator configurations.
     *
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          array(),
     *      ),
     *      array(
     *          array(
     *              'IntegerValueValidator' => array(
     *                  'minIntegerValue' => 0,
     *                  'maxIntegerValue' => 23,
     *              )
     *          )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideValidValidatorConfiguration()
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

    /**
     * Provide data sets with validator configurations which are considered
     * invalid by the {@link validateValidatorConfiguration()} method. The
     * method must return a non-empty array of validation errors when receiving
     * one of the provided values.
     *
     * Returns an array of data provider sets with a single argument: A valid
     * set of validator configurations.
     *
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          array(
     *              'NonExistentValidator' => array(),
     *          ),
     *      ),
     *      array(
     *          array(
     *              // Typos
     *              'InTEgervALUeVALIdator' => array(
     *                  'iinIntegerValue' => 0,
     *                  'maxIntegerValue' => 23,
     *              )
     *          )
     *      ),
     *      array(
     *          array(
     *              'IntegerValueValidator' => array(
     *                  // Incorrect value types
     *                  'minIntegerValue' => true,
     *                  'maxIntegerValue' => false,
     *              )
     *          )
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInvalidValidatorConfiguration()
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

    /**
     * Provides data sets with validator configuration and/or field settings and
     * field value which are considered valid by the {@link validate()} method.
     *
     * ATTENTION: This is a default implementation, which must be overwritten if
     * a FieldType supports validation!
     *
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          array(
     *              "validatorConfiguration" => array(
     *                  "StringLengthValidator" => array(
     *                      "minStringLength" => 2,
     *                      "maxStringLength" => 10,
     *                  ),
     *              ),
     *          ),
     *          new TextLineValue( "lalalala" ),
     *      ),
     *      array(
     *          array(
     *              "fieldSettings" => array(
     *                  'isMultiple' => true
     *              ),
     *          ),
     *          new CountryValue(
     *              array(
     *                  "BE" => array(
     *                      "Name" => "Belgium",
     *                      "Alpha2" => "BE",
     *                      "Alpha3" => "BEL",
     *                      "IDC" => 32,
     *                  ),
     *              ),
     *          ),
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideValidDataForValidate()
    {
        return [
            [
                [
                    'validatorConfiguration' => [
                        'RelationListValueValidator' => [
                            'selectionLimit' => 0,
                        ],
                    ],
                ],
                new Value([5, 6, 7]),
            ],
            [
                [
                    'validatorConfiguration' => [
                        'RelationListValueValidator' => [
                            'selectionLimit' => 1,
                        ],
                    ],
                ],
                new Value([5]),
            ],
            [
                [
                    'validatorConfiguration' => [
                        'RelationListValueValidator' => [
                            'selectionLimit' => 3,
                        ],
                    ],
                ],
                new Value([5, 6]),
            ],
            [
                [
                    'validatorConfiguration' => [
                        'RelationListValueValidator' => [
                            'selectionLimit' => 3,
                        ],
                    ],
                ],
                new Value([]),
            ],
        ];
    }

    /**
     * Provides data sets with validator configuration and/or field settings,
     * field value and corresponding validation errors returned by
     * the {@link validate()} method.
     *
     * ATTENTION: This is a default implementation, which must be overwritten
     * if a FieldType supports validation!
     *
     * For example:
     *
     * <code>
     *  return array(
     *      array(
     *          array(
     *              "validatorConfiguration" => array(
     *                  "IntegerValueValidator" => array(
     *                      "minIntegerValue" => 5,
     *                      "maxIntegerValue" => 10
     *                  ),
     *              ),
     *          ),
     *          new IntegerValue( 3 ),
     *          array(
     *              new ValidationError(
     *                  "The value can not be lower than %size%.",
     *                  null,
     *                  array(
     *                      "%size%" => 5
     *                  ),
     *              ),
     *          ),
     *      ),
     *      array(
     *          array(
     *              "fieldSettings" => array(
     *                  "isMultiple" => false
     *              ),
     *          ),
     *          new CountryValue(
     *              "BE" => array(
     *                  "Name" => "Belgium",
     *                  "Alpha2" => "BE",
     *                  "Alpha3" => "BEL",
     *                  "IDC" => 32,
     *              ),
     *              "FR" => array(
     *                  "Name" => "France",
     *                  "Alpha2" => "FR",
     *                  "Alpha3" => "FRA",
     *                  "IDC" => 33,
     *              ),
     *          )
     *      ),
     *      array(
     *          new ValidationError(
     *              "Field definition does not allow multiple countries to be selected."
     *          ),
     *      ),
     *      // ...
     *  );
     * </code>
     *
     * @return array
     */
    public function provideInvalidDataForValidate()
    {
        return [
            [
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

    /**
     * @covers \Ibexa\Core\FieldType\Relation\Type::getRelations
     */
    public function testGetRelations()
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
        return 'ezobjectrelationlist';
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
