<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Validation;

use Ibexa\Contracts\Core\Validation\StructWrapperValidator;
use Ibexa\Contracts\Core\Validation\ValidationStructWrapperInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \Ibexa\Contracts\Core\Validation\StructWrapperValidator
 */
final class StructWrapperValidatorTest extends TestCase
{
    /** @var ValidatorInterface&MockObject */
    private ValidatorInterface $validator;

    private StructWrapperValidator $structValidator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->structValidator = new StructWrapperValidator($this->validator);
    }

    public function testAssertValidStructWithValidStruct(): void
    {
        $struct = new stdClass();
        $initialErrors = $this->createMock(ConstraintViolationListInterface::class);
        $initialErrors->method('count')->willReturn(0);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $struct,
                null,
                ['Default', 'group']
            )->willReturn($initialErrors);

        $errors = $this->structValidator->validate(new stdClass(), null, ['Default', 'group']);
        self::assertSame($initialErrors, $errors);
        self::assertCount(0, $errors);
    }

    public function testAssertValidStructWithInvalidStruct(): void
    {
        $initialError = $this->createExampleConstraintViolation();
        $initialErrors = $this->createExampleConstraintViolationList($initialError);

        $this->validator
            ->method('validate')
            ->with(
                new stdClass(),
                null,
                ['Default', 'group']
            )->willReturn($initialErrors);

        $errors = $this->structValidator->validate(new stdClass(), null, ['Default', 'group']);
        self::assertSame($initialErrors, $errors);
        self::assertCount(1, $errors);

        $error = $errors->get(0);
        self::assertSame($initialError, $error);
        self::assertSame('validation error', $error->getMessage());
        self::assertSame('struct.property', $error->getPropertyPath());
    }

    public function testAssertValidStructWithInvalidWrapperStruct(): void
    {
        $initialError = $this->createExampleConstraintViolation();
        $initialErrors = $this->createExampleConstraintViolationList($initialError);

        $wrapper = $this->createMock(ValidationStructWrapperInterface::class);

        $struct = new stdClass();
        $wrapper->expects(self::once())
            ->method('getStruct')
            ->willReturn($struct);

        $this->validator
            ->method('validate')
            ->with(
                $wrapper,
                null,
                ['Default', 'group']
            )->willReturn($initialErrors);

        $errors = $this->structValidator->validate($wrapper, null, ['Default', 'group']);
        self::assertNotSame($initialErrors, $errors);
        self::assertCount(1, $errors);

        $error = $errors->get(0);
        self::assertNotSame($error, $initialError);
        self::assertSame('validation error', $error->getMessage());
        self::assertSame($struct, $error->getRoot());
        self::assertSame('property', $error->getPropertyPath());
    }

    public function testValidateProperty(): void
    {
        $initialError = $this->createExampleConstraintViolation();
        $initialErrors = $this->createExampleConstraintViolationList($initialError);

        $object = new stdClass();
        $propertyName = 'foobar';
        $group = ['Default', 'group'];
        $this->validator->expects(self::once())
            ->method('validateProperty')
            ->with($object, $propertyName, $group)
            ->willReturn($initialErrors);

        $this->structValidator->validateProperty($object, $propertyName, $group);
    }

    public function testValidatePropertyValue(): void
    {
        $initialError = $this->createExampleConstraintViolation();
        $initialErrors = $this->createExampleConstraintViolationList($initialError);

        $object = new stdClass();
        $propertyName = 'foobar';
        $value = 'someValue';
        $group = ['Default', 'group'];
        $this->validator->expects(self::once())
            ->method('validatePropertyValue')
            ->with($object, $propertyName, $value, $group)
            ->willReturn($initialErrors);

        $this->structValidator->validatePropertyValue($object, $propertyName, $value, $group);
    }

    private function createExampleConstraintViolation(): ConstraintViolationInterface
    {
        return new ConstraintViolation(
            'validation error',
            null,
            [],
            '',
            'struct.property',
            'example'
        );
    }

    private function createExampleConstraintViolationList(
        ConstraintViolationInterface ...$errors
    ): ConstraintViolationListInterface {
        return new ConstraintViolationList($errors);
    }
}
