<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Validation;

use Ibexa\Contracts\Core\Validation\StructValidator;
use Ibexa\Contracts\Core\Validation\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \Ibexa\Contracts\Core\Validation\StructValidator
 */
final class StructValidatorTest extends TestCase
{
    /** @var \Symfony\Component\Validator\Validator\ValidatorInterface&\PHPUnit\Framework\MockObject\MockObject) */
    private ValidatorInterface $validator;

    private StructValidator $structValidator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->structValidator = new StructValidator($this->validator);
    }

    public function testAssertValidStructWithValidStruct(): void
    {
        $struct = new stdClass();
        $errors = $this->createMock(ConstraintViolationListInterface::class);
        $errors->method('count')->willReturn(0);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with(
                $struct,
                null,
                ['Default', 'group']
            )->willReturn($errors);

        $this->structValidator->assertValidStruct('struct', new stdClass(), ['group']);
    }

    public function testAssertValidStructWithInvalidStruct(): void
    {
        $errors = $this->createExampleConstraintViolationList(
            $this->createExampleConstraintViolation()
        );

        $this->validator
            ->method('validate')
            ->with(
                new stdClass(),
                null,
                ['Default', 'group']
            )->willReturn($errors);

        try {
            $this->structValidator->assertValidStruct('struct', new stdClass(), ['group']);
        } catch (ValidationFailedException $e) {
            self::assertSame("Argument 'struct->property' is invalid: validation error", $e->getMessage());
            self::assertSame($errors, $e->getErrors());
        }
    }

    private function createExampleConstraintViolation(): ConstraintViolationInterface
    {
        return new ConstraintViolation(
            'validation error',
            null,
            [],
            '',
            'property',
            'example'
        );
    }

    private function createExampleConstraintViolationList(
        ConstraintViolationInterface $error
    ): ConstraintViolationListInterface {
        return new ConstraintViolationList([$error]);
    }
}
