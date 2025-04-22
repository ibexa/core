<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\Generic;

use Ibexa\Contracts\Core\FieldType\ValidationError;
use Ibexa\Contracts\Core\FieldType\ValueSerializerInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Tests\Core\FieldType\BaseFieldTypeTestCase;
use Ibexa\Tests\Core\FieldType\Generic\Stubs\Type;
use Ibexa\Tests\Core\FieldType\Generic\Stubs\Type as GenericFieldTypeStub;
use Ibexa\Tests\Core\FieldType\Generic\Stubs\Value;
use Ibexa\Tests\Core\FieldType\Generic\Stubs\Value as GenericFieldValueStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GenericTest extends BaseFieldTypeTestCase
{
    private ValueSerializerInterface & MockObject $serializer;

    private ValidatorInterface & MockObject $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = $this->createSerializerMock();
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    /**
     * @dataProvider provideValidDataForValidate
     */
    public function testValidateValid($fieldDefinitionData, $value): void
    {
        $this->validator
            ->method('validate')
            ->with($value, null)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        parent::testValidateValid($fieldDefinitionData, $value);
    }

    /**
     * @dataProvider provideInvalidDataForValidate
     */
    public function testValidateInvalid($fieldDefinitionData, $value, $errors): void
    {
        $constraintViolationList = new ConstraintViolationList(array_map(static function (ValidationError $error): ConstraintViolation {
            return new ConstraintViolation((string) $error->getTranslatableMessage());
        }, $errors));

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

    protected function createFieldTypeUnderTest(): Type
    {
        return new GenericFieldTypeStub($this->serializer, $this->validator);
    }

    /**
     * @return array{}
     */
    protected function getValidatorConfigurationSchemaExpectation(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    protected function getSettingsSchemaExpectation(): array
    {
        return [];
    }

    protected function getEmptyValueExpectation(): Value
    {
        return new GenericFieldValueStub();
    }

    /**
     * @phpstan-return list<array{int, string}>
     */
    public function provideInvalidInputForAcceptValue(): array
    {
        return [
            [
                23,
                InvalidArgumentException::class,
            ],
        ];
    }

    /**
     * @phpstan-return list<array{string|null|\Ibexa\Contracts\Core\FieldType\Value, \Ibexa\Contracts\Core\FieldType\Value}>
     */
    public function provideValidInputForAcceptValue(): array
    {
        return [
            [
                null,
                new GenericFieldValueStub(),
            ],
            [
                '{"value": "foo"}',
                new GenericFieldValueStub('foo'),
            ],
            [
                new GenericFieldValueStub('foo'),
                new GenericFieldValueStub('foo'),
            ],
        ];
    }

    /**
     * @phpstan-return list<array{\Ibexa\Contracts\Core\FieldType\Value, array{value: string}|null}>
     */
    public function provideInputForToHash(): array
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

    /**
     * @phpstan-return list<array{array{value: string}|null, \Ibexa\Contracts\Core\FieldType\Value}>
     */
    public function provideInputForFromHash(): array
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

    /**
     * @phpstan-return list<array{\Ibexa\Contracts\Core\FieldType\Value, string, array<string, mixed>, string}>
     */
    public function provideDataForGetName(): array
    {
        return [
            [new GenericFieldValueStub('This is a generic value.'), 'This is a generic value.', [], 'en_GB'],
        ];
    }

    private function createSerializerMock(): ValueSerializerInterface & MockObject
    {
        $serializer = $this->createMock(ValueSerializerInterface::class);

        $serializer
            ->method('decode')
            ->willReturnCallback(static function (string $json) {
                return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            });

        $serializer
            ->method('normalize')
            ->willReturnCallback(static function (GenericFieldValueStub $value): array {
                return [
                    'value' => $value->getValue(),
                ];
            });

        $serializer
            ->method('denormalize')
            ->willReturnCallback(static function (array $data, string $valueClass): Value {
                self::assertEquals(GenericFieldValueStub::class, $valueClass);

                return new GenericFieldValueStub($data['value']);
            });

        return $serializer;
    }
}
