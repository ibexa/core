<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\Generic;

use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\FieldType\Value as FieldTypeValue;
use Ibexa\Contracts\Core\FieldType\ValueSerializerInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Tests\Core\FieldType\BaseFieldTypeTestCase;
use Ibexa\Tests\Core\FieldType\Generic\Stubs\Type as GenericFieldTypeStub;
use Ibexa\Tests\Core\FieldType\Generic\Stubs\Value as GenericFieldValueStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GenericTest extends BaseFieldTypeTestCase
{
    private ValueSerializerInterface $serializer;

    private ValidatorInterface & MockObject $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = $this->createSerializerMock();
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    /**
     * @dataProvider provideValidDataForValidate
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testValidateValid(array $fieldDefinitionData, Value $value): void
    {
        $this->validator
            ->method('validate')
            ->with($value, null)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        parent::testValidateValid($fieldDefinitionData, $value);
    }

    /**
     * @dataProvider provideInvalidDataForValidate
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testValidateInvalid(array $fieldDefinitionData, FieldTypeValue $value, array $errors): void
    {
        $constraintViolationList = new ConstraintViolationList(
            array_map(
                static fn (ValidationError $error) => new ConstraintViolation(
                    (string)$error->getTranslatableMessage(),
                    null,
                    [],
                    null,
                    null,
                    null
                ),
                $errors
            )
        );

        $this->validator
            ->method('validate')
            ->with($value, null)
            ->willReturn($constraintViolationList);

        parent::testValidateInvalid($fieldDefinitionData, $value, $errors);
    }

    protected function provideFieldTypeIdentifier(): string
    {
        return 'generic';
    }

    protected function createFieldTypeUnderTest(): GenericFieldTypeStub
    {
        return new GenericFieldTypeStub($this->serializer, $this->validator);
    }

    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [];
    }

    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    protected function getEmptyValueExpectation(): GenericFieldValueStub
    {
        return new GenericFieldValueStub();
    }

    public function provideInvalidInputForAcceptValue(): iterable
    {
        return [
            [
                23,
                InvalidArgumentException::class,
            ],
        ];
    }

    public function provideValidInputForAcceptValue(): iterable
    {
        yield 'null' => [
            null,
            new GenericFieldValueStub(),
        ];
        yield 'array' => [
            '{"value": "foo"}',
            new GenericFieldValueStub('foo'),
        ];
        yield 'value' => [
            new GenericFieldValueStub('foo'),
            new GenericFieldValueStub('foo'),
        ];
    }

    public function provideInputForToHash(): iterable
    {
        return [
            [
                new GenericFieldValueStub(),
                null,
            ],
            [
                new GenericFieldValueStub('foo'),
                ['value' => 'foo'],
            ],
        ];
    }

    public function provideInputForFromHash(): iterable
    {
        return [
            [
                null,
                new GenericFieldValueStub(),
            ],
            [
                ['value' => 'foo'],
                new GenericFieldValueStub('foo'),
            ],
        ];
    }

    public function provideDataForGetName(): array
    {
        return [
            [new GenericFieldValueStub('This is a generic value.'), 'This is a generic value.', [], 'en_GB'],
        ];
    }

    private function createSerializerMock(): ValueSerializerInterface
    {
        $serializer = $this->createMock(ValueSerializerInterface::class);

        $serializer
            ->method('decode')
            ->willReturnCallback(static function (string $json) {
                return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            });

        $serializer
            ->method('normalize')
            ->willReturnCallback(static function (GenericFieldValueStub $value) {
                return [
                    'value' => $value->getValue(),
                ];
            });

        $serializer
            ->method('denormalize')
            ->willReturnCallback(static function (array $data, string $valueClass) {
                self::assertEquals(GenericFieldValueStub::class, $valueClass);

                return new GenericFieldValueStub($data['value']);
            });

        return $serializer;
    }
}
