<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\FieldType\Value as FieldTypeValue;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition as APIFieldDefinition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class BaseFieldTypeTestCase extends TestCase
{
    /**
     * Generic cache for the getFieldTypeUnderTest() method.
     */
    private FieldType $fieldTypeUnderTest;

    /**
     * Returns the identifier of the field type under test.
     */
    abstract protected function provideFieldTypeIdentifier(): string;

    /**
     * Returns the field type under test.
     *
     * This method is used by all test cases to retrieve the field type under
     * test. Create the FieldType instance using mocks from the provided
     * get*Mock() methods and/or custom get*Mock() implementations. You MUST
     * NOT rely on the test-case-wide caching of the field type, just return
     * a new instance from this method!
     */
    abstract protected function createFieldTypeUnderTest(): FieldType;

    /**
     * Returns the validator configuration schema expected from the field type.
     *
     * @return array<string, mixed>
     */
    abstract protected function getValidatorConfigurationSchemaExpectation(): array;

    /**
     * Returns the settings schema expected from the field type.
     *
     * @return array<string, mixed>
     */
    abstract protected function getSettingsSchemaExpectation(): array;

    /**
     * Returns the empty value expected from the field type.
     */
    abstract protected function getEmptyValueExpectation(): FieldTypeValue;

    /**
     * Data provider for invalid input to acceptValue().
     *
     * Returns an array of data provider sets with 2 arguments: 1. The invalid
     * input to acceptValue(), 2. The expected exception type as a string. For
     * example:
     *
     * ```
     *  yield [
     *          new \stdClass(),
     *          InvalidArgumentException::class,
     *  ];
     *  yield [
     *          [],
     *          InvalidArgumentException::class,
     *  ];
     * ```
     *
     * @phpstan-return iterable<array{
     *      mixed,
     *      class-string<\Throwable>
     *  }>
     */
    abstract public function provideInvalidInputForAcceptValue(): iterable;

    /**
     * Data provider for valid input to acceptValue().
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to acceptValue(), 2. The expected return value from acceptValue().
     * For example:
     *
     * ```
     * yield 'null input and output' => [
     *          null,
     *          null
     * ];
     * yield 'string input and BinaryFileValue output' => [
     *          __FILE__,
     *          new BinaryFileValue([
     *              'path' => __FILE__,
     *              'fileName' => basename(__FILE__),
     *              'fileSize' => filesize(__FILE__),
     *              'downloadCount' => 0,
     *              'mimeType' => 'text/plain',
     *          ])
     * ];
     * ```
     *
     * @phpstan-return iterable<string, array{mixed, Value}>
     */
    abstract public function provideValidInputForAcceptValue(): iterable;

    /**
     * Provide input for the toHash() method.
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to toHash(), 2. The expected return value from toHash().
     * For example:
     *
     * ```
     * yield 'null input, null result' => [
     *     null,
     *     null
     * ];
     *
     * yield 'binary file value input, hash result' => [
     *     new BinaryFileValue([
     *         'path' => 'some/file/here',
     *         'fileName' => 'sindelfingen.jpg',
     *         'fileSize' => 2342,
     *         'downloadCount' => 0,
     *         'mimeType' => 'image/jpeg',
     *     ]),
     *     [
     *         'path' => 'some/file/here',
     *         'fileName' => 'sindelfingen.jpg',
     *         'fileSize' => 2342,
     *         'downloadCount' => 0,
     *         'mimeType' => 'image/jpeg',
     *     ]
     * ];
     * ```
     *
     * @phpstan-return iterable<array{Value, mixed}>
     */
    abstract public function provideInputForToHash(): iterable;

    /**
     * Provide input to fromHash() method.
     *
     * Returns an array of data provider sets with 2 arguments: 1. The valid
     * input to fromHash(), 2. The expected return value from fromHash().
     * For example:
     *
     * ```
     * yield [
     *           null,
     *           null,
     * ];
     * yield [
     *          [
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ],
     *          new BinaryFileValue([
     *              'path' => 'some/file/here',
     *              'fileName' => 'sindelfingen.jpg',
     *              'fileSize' => 2342,
     *              'downloadCount' => 0,
     *              'mimeType' => 'image/jpeg',
     *          ])
     * ];
     * ```
     *
     * @phpstan-return iterable<array{mixed, mixed}>
     */
    abstract public function provideInputForFromHash(): iterable;

    /**
     * Provides data for the getName() test.
     *
     * @phpstan-return array<array{
     *     0: FieldTypeValue,
     *     1: string,
     *     2?: array<string, mixed>,
     *     3?: string
     * }>
     */
    abstract public function provideDataForGetName(): array;

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
     * ```
     *  return [
     *      [
     *          true,
     *      ],
     *      [
     *          ['nonExistentKey' => 2]
     *      ],
     *      // ...
     *  ];
     * ```
     *
     * @phpstan-return array<array{mixed}>
     */
    public function provideInValidFieldSettings(): array
    {
        return [
            [
                ['nonempty'],
            ],
        ];
    }

    /**
     * Provide data sets with field settings which are considered invalid by the
     * {@link validateFieldSettings()} method.
     *
     * ATTENTION: This is a default implementation, which must be overwritten
     * if a FieldType supports field settings!
     *
     * Yields data provider sets with a single argument: A valid set of field settings.
     * For example:
     *
     * ```
     * yield [
     *      [],
     * ],
     * yield [
     *      ['rows' => 2]
     * ];
     * ```
     *
     * @phpstan-return iterable<array{mixed}>
     */
    public function provideValidFieldSettings(): iterable
    {
        return [
            [
                [],
            ],
        ];
    }

    /**
     * Provide data sets with validator configurations which are considered
     * valid by the {@link validateValidatorConfiguration()} method.
     *
     * ATTENTION: This is a default implementation, which must be overwritten
     * if a FieldType supports validators!
     *
     * Returns an array of data provider sets with a single argument: A valid
     * set of validator configurations.
     *
     * For example:
     *
     * ```
     * return [
     *      [
     *          [],
     *      ],
     *      [
     *          [
     *              'IntegerValueValidator' => [
     *                  'minIntegerValue' => 0,
     *                  'maxIntegerValue' => 23,
     *              ]
     *          ]
     *      ],
     *      // ...
     *  ];
     * ```
     *
     * @phpstan-return array<array{mixed}>
     */
    public function provideValidValidatorConfiguration(): array
    {
        return [
            [
                [],
            ],
        ];
    }

    /**
     * Provide data sets with validator configurations which are considered
     * invalid by the {@link validateValidatorConfiguration()} method. The
     * method must return a non-empty array of valiation errors when receiving
     * one of the provided values.
     *
     * ATTENTION: This is a default implementation, which must be overwritten
     * if a FieldType supports validators!
     *
     * Returns an array of data provider sets with a single argument: A valid
     * set of validator configurations.
     *
     * For example:
     *
     * ```
     *  return [
     *      [
     *          [
     *              'NonExistentValidator' => [],
     *          ],
     *      ],
     *      [
     *          [
     *              'IntegerValueValidator' => [
     *                  'minIntegerValue' => true,
     *                  'maxIntegerValue' => false,
     *              ]
     *          ]
     *      ],
     *      // ...
     *  ];
     * ```
     *
     * @phpstan-return array<array{mixed}>
     */
    public function provideInvalidValidatorConfiguration(): array
    {
        return [
            [
                [
                    'NonExistentValidator' => [],
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
     * ```
     * yield 'some text line validation' => [
     *          [
     *              'validatorConfiguration' => [
     *                  'StringLengthValidator' => [
     *                      'minStringLength' => 2,
     *                      'maxStringLength' => 10,
     *                  ],
     *              ],
     *          ],
     *          new TextLineValue('lalalala'),
     * ];
     * yield 'some country value validation' => [
     *          [
     *              'fieldSettings' => [
     *                  'isMultiple' => true
     *              ],
     *          ],
     *          new CountryValue([
     *              'BE' => [
     *                  'Name' => 'Belgium',
     *                  'Alpha2' => 'BE',
     *                  'Alpha3' => 'BEL',
     *                  'IDC' => 32,
     *              ],
     *          ]),
     * ];
     * ```
     *
     * @phpstan-return iterable<string, array{array<string, mixed>, Value}>
     */
    public function provideValidDataForValidate(): iterable
    {
        yield 'empty field definition data' => [
            [],
            $this->createMock(FieldTypeValue::class),
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
     * ```
     * yield 'integer value below minimum' => [
     *     [
     *         'validatorConfiguration' => [
     *             'IntegerValueValidator' => [
     *                 'minIntegerValue' => 5,
     *                 'maxIntegerValue' => 10
     *             ],
     *         ],
     *     ],
     *     new IntegerValue(3),
     *     [
     *         new ValidationError(
     *             'The value can not be lower than %size%.',
     *             null,
     *             [
     *                 '%size%' => 5
     *             ],
     *         ),
     *     ],
     * ];
     * yield 'multiple countries not allowed' => [
     *     [
     *         'fieldSettings' => [
     *             'isMultiple' => false
     *         ],
     *     ],
     *     new CountryValue([
     *         'BE' => [
     *             'Name' => 'Belgium',
     *             'Alpha2' => 'BE',
     *             'Alpha3' => 'BEL',
     *             'IDC' => 32,
     *         ],
     *         'FR' => [
     *             'Name' => 'France',
     *             'Alpha2' => 'FR',
     *             'Alpha3' => 'FRA',
     *             'IDC' => 33,
     *         ],
     *     ]),
     *     [
     *         new ValidationError(
     *             'Field definition does not allow multiple countries to be selected.'
     *         ),
     *     ],
     * ];
     * ```
     *
     * @phpstan-return iterable<string, array{
     *     array<string, mixed>,
     *     FieldTypeValue,
     *     array<ValidationError>
     * }>
     */
    public function provideInvalidDataForValidate(): iterable
    {
        yield 'invalid field definition data with no errors' => [
            [],
            $this->createMock(FieldTypeValue::class),
            [],
        ];
    }

    /**
     * Retrieves a test wide cached version of the field type under test.
     *
     * Uses {@link createFieldTypeUnderTest()} to create the instance initially.
     */
    protected function getFieldTypeUnderTest(): FieldType
    {
        if (!isset($this->fieldTypeUnderTest)) {
            $this->fieldTypeUnderTest = $this->createFieldTypeUnderTest();
        }

        return $this->fieldTypeUnderTest;
    }

    public function testGetFieldTypeIdentifier(): void
    {
        self::assertSame(
            $this->provideFieldTypeIdentifier(),
            $this->getFieldTypeUnderTest()->getFieldTypeIdentifier()
        );
    }

    /**
     * @dataProvider provideDataForGetName
     *
     * @param array<string, mixed> $fieldSettings
     */
    public function testGetName(
        FieldTypeValue $value,
        string $expected,
        array $fieldSettings = [],
        string $languageCode = 'en_GB'
    ): void {
        $fieldDefinitionMock = $this->getFieldDefinitionMock($fieldSettings);

        self::assertSame(
            $expected,
            $this->getFieldTypeUnderTest()->getName($value, $fieldDefinitionMock, $languageCode)
        );
    }

    public function testValidatorConfigurationSchema(): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        self::assertSame(
            $this->getValidatorConfigurationSchemaExpectation(),
            $fieldType->getValidatorConfigurationSchema(),
            'Validator configuration schema not returned correctly.'
        );
    }

    public function testSettingsSchema(): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        self::assertSame(
            $this->getSettingsSchemaExpectation(),
            $fieldType->getSettingsSchema(),
            'Settings schema not returned correctly.'
        );
    }

    public function testEmptyValue(): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        self::assertEquals(
            $this->getEmptyValueExpectation(),
            $fieldType->getEmptyValue()
        );
    }

    /**
     * @dataProvider provideValidInputForAcceptValue
     *
     * @throws InvalidArgumentException
     */
    public function testAcceptValue(
        mixed $inputValue,
        FieldTypeValue $expectedOutputValue
    ): void {
        $fieldType = $this->getFieldTypeUnderTest();

        $outputValue = $fieldType->acceptValue($inputValue);

        self::assertEquals(
            $expectedOutputValue,
            $outputValue,
            'acceptValue() did not convert properly.'
        );
    }

    /**
     * Tests that default empty value is unchanged by the `acceptValue` method.
     *
     * @throws InvalidArgumentException
     */
    public function testAcceptGetEmptyValue(): void
    {
        $fieldType = $this->getFieldTypeUnderTest();
        $emptyValue = $fieldType->getEmptyValue();

        $acceptedEmptyValue = $fieldType->acceptValue($emptyValue);

        self::assertEquals(
            $emptyValue,
            $acceptedEmptyValue,
            'acceptValue() did not convert properly.'
        );
    }

    /**
     * @dataProvider provideInvalidInputForAcceptValue
     *
     * @phpstan-param class-string<\Throwable> $expectedException
     *
     * @throws InvalidArgumentException
     */
    public function testAcceptValueFailsOnInvalidValues(
        mixed $inputValue,
        string $expectedException
    ): void {
        $fieldType = $this->getFieldTypeUnderTest();

        $this->expectException($expectedException);
        $fieldType->acceptValue($inputValue);
    }

    /**
     * @dataProvider provideInputForToHash
     */
    public function testToHash(
        FieldTypeValue $inputValue,
        mixed $expectedResult
    ): void {
        $fieldType = $this->getFieldTypeUnderTest();

        $actualResult = $fieldType->toHash($inputValue);

        $this->assertIsValidHashValue($actualResult);

        if (is_object($expectedResult) || is_array($expectedResult)) {
            self::assertEquals(
                $expectedResult,
                $actualResult,
                'toHash() method did not create expected result.'
            );
        } else {
            self::assertSame(
                $expectedResult,
                $actualResult,
                'toHash() method did not create expected result.'
            );
        }
    }

    /**
     * @dataProvider provideInputForFromHash
     *
     * @param array<mixed>|null $inputHash
     */
    public function testFromHash(
        mixed $inputHash,
        mixed $expectedResult
    ): void {
        $this->assertIsValidHashValue($inputHash);

        $fieldType = $this->getFieldTypeUnderTest();

        $actualResult = $fieldType->fromHash($inputHash);

        if (is_object($expectedResult) || is_array($expectedResult)) {
            self::assertEquals(
                $expectedResult,
                $actualResult,
                'fromHash() method did not create expected result.'
            );
        } else {
            self::assertSame(
                $expectedResult,
                $actualResult,
                'fromHash() method did not create expected result.'
            );
        }
    }

    public function testEmptyValueIsEmpty(): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        self::assertTrue(
            $fieldType->isEmptyValue($fieldType->getEmptyValue())
        );
    }

    /**
     * @dataProvider provideValidFieldSettings
     */
    public function testValidateFieldSettingsValid(mixed $inputSettings): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        $validationResult = $fieldType->validateFieldSettings($inputSettings);

        self::assertIsArray(
            $validationResult,
            'The method validateFieldSettings() must return an array.'
        );
        self::assertEquals(
            [],
            $validationResult,
            'validateFieldSettings() did not consider the input settings valid.'
        );
    }

    /**
     * @dataProvider provideInvalidFieldSettings
     */
    public function testValidateFieldSettingsInvalid(mixed $inputSettings): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        $validationResult = $fieldType->validateFieldSettings($inputSettings);

        self::assertIsArray(
            $validationResult,
            'The method validateFieldSettings() must return an array.'
        );

        self::assertNotEquals(
            [],
            $validationResult,
            'validateFieldSettings() did consider the input settings valid, which should be invalid.'
        );

        foreach ($validationResult as $actualResultElement) {
            self::assertInstanceOf(
                ValidationError::class,
                $actualResultElement,
                'Validation result of incorrect type.'
            );
        }
    }

    /**
     * @dataProvider provideValidValidatorConfiguration
     */
    public function testValidateValidatorConfigurationValid(mixed $inputConfiguration): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        $validationResult = $fieldType->validateValidatorConfiguration($inputConfiguration);

        self::assertIsArray(
            $validationResult,
            'The method validateValidatorConfiguration() must return an array.'
        );
        self::assertEquals(
            [],
            $validationResult,
            'validateValidatorConfiguration() did not consider the input configuration valid.'
        );
    }

    /**
     * @dataProvider provideInvalidValidatorConfiguration
     */
    public function testValidateValidatorConfigurationInvalid(mixed $inputConfiguration): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        $validationResult = $fieldType->validateValidatorConfiguration($inputConfiguration);

        self::assertIsArray(
            $validationResult,
            'The method validateValidatorConfiguration() must return an array.'
        );

        self::assertNotEquals(
            [],
            $validationResult,
            'validateValidatorConfiguration() did consider the input settings valid, which should be invalid.'
        );

        foreach ($validationResult as $actualResultElement) {
            self::assertInstanceOf(
                ValidationError::class,
                $actualResultElement,
                'Validation result of incorrect type.'
            );
        }
    }

    /**
     * @dataProvider provideValidFieldSettings
     */
    public function testFieldSettingsToHash(mixed $inputSettings): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        $hash = $fieldType->fieldSettingsToHash($inputSettings);

        $this->assertIsValidHashValue($hash);
    }

    /**
     * @dataProvider provideValidValidatorConfiguration
     */
    public function testValidatorConfigurationToHash(mixed $inputConfiguration): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        $hash = $fieldType->validatorConfigurationToHash($inputConfiguration);

        $this->assertIsValidHashValue($hash);
    }

    /**
     * @dataProvider provideValidFieldSettings
     */
    public function testFieldSettingsFromHash(mixed $inputSettings): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        $hash = $fieldType->fieldSettingsToHash($inputSettings);
        $restoredSettings = $fieldType->fieldSettingsFromHash($hash);

        self::assertEquals($inputSettings, $restoredSettings);
    }

    /**
     * @dataProvider provideValidValidatorConfiguration
     */
    public function testValidatorConfigurationFromHash(mixed $inputConfiguration): void
    {
        $fieldType = $this->getFieldTypeUnderTest();

        $hash = $fieldType->validatorConfigurationToHash($inputConfiguration);
        $restoredConfiguration = $fieldType->validatorConfigurationFromHash($hash);

        self::assertEquals($inputConfiguration, $restoredConfiguration);
    }

    /**
     * Asserts that the given $actualHash complies to the rules for hashes.
     *
     * @param string[] $keyChain
     */
    protected function assertIsValidHashValue(
        mixed $actualHash,
        array $keyChain = []
    ): void {
        switch ($actualHashType = gettype($actualHash)) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
            case 'NULL':
                // All valid, return
                return;

            case 'array':
                foreach ($actualHash as $key => $childHash) {
                    $this->assertIsValidHashValue(
                        $childHash,
                        array_merge($keyChain, [$key])
                    );
                }

                return;

            default:
                self::fail(
                    sprintf(
                        'Value for $hash[%s] is of invalid type "%s".',
                        implode('][', $keyChain),
                        $actualHashType
                    )
                );
        }
    }

    /**
     * @dataProvider provideValidDataForValidate
     *
     * @param array<string, mixed> $fieldDefinitionData
     *
     * @throws InvalidArgumentException
     */
    public function testValidateValid(
        array $fieldDefinitionData,
        FieldTypeValue $value
    ): void {
        $validationErrors = $this->doValidate($fieldDefinitionData, $value);

        self::assertIsArray($validationErrors);
        self::assertEmpty($validationErrors, "Got value:\n" . var_export($validationErrors, true));
    }

    /**
     * @dataProvider provideInvalidDataForValidate
     *
     * @param array<string, mixed> $fieldDefinitionData
     * @param ValidationError[] $errors
     *
     * @throws InvalidArgumentException
     */
    public function testValidateInvalid(
        array $fieldDefinitionData,
        FieldTypeValue $value,
        array $errors
    ): void {
        $validationErrors = $this->doValidate($fieldDefinitionData, $value);

        self::assertIsArray($validationErrors);
        self::assertEquals($errors, $validationErrors);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @param array<string, mixed> $fieldDefinitionData
     *
     * @phpstan-return array<ValidationError>
     */
    protected function doValidate(
        array $fieldDefinitionData,
        FieldTypeValue $value
    ): array {
        $fieldType = $this->getFieldTypeUnderTest();

        /** @var FieldDefinition|MockObject $fieldDefinitionMock */
        $fieldDefinitionMock = $this->createMock(APIFieldDefinition::class);

        foreach ($fieldDefinitionData as $method => $data) {
            if ($method === 'validatorConfiguration') {
                $fieldDefinitionMock
                    ->method('getValidatorConfiguration')
                    ->willReturn($data);
            }

            if ($method === 'fieldSettings') {
                $fieldDefinitionMock
                    ->method('getFieldSettings')
                    ->willReturn($data);
            }
        }

        return $fieldType->validate($fieldDefinitionMock, $value);
    }

    /**
     * @param array<string, mixed> $fieldSettings
     */
    protected function getFieldDefinitionMock(array $fieldSettings): APIFieldDefinition & MockObject
    {
        $fieldDefinitionMock = $this->createMock(APIFieldDefinition::class);
        $fieldDefinitionMock
            ->method('getFieldSettings')
            ->willReturn($fieldSettings);

        return $fieldDefinitionMock;
    }
}
