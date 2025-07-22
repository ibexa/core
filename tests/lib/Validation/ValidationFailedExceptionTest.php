<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Validation;

use Ibexa\Contracts\Core\Validation\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class ValidationFailedExceptionTest extends TestCase
{
    public function testSingleError(): void
    {
        $errors = new ConstraintViolationList([
            new ConstraintViolation('__error__', null, [], null, '__property_path__', null),
        ]);

        $exception = new ValidationFailedException('__argument_name__', $errors);

        self::assertSame(
            "Argument '__argument_name__->__property_path__' is invalid: __error__",
            $exception->getMessage(),
        );
        self::assertSame($errors, $exception->getErrors());
    }

    public function testMultipleErrors(): void
    {
        $errors = new ConstraintViolationList([
            new ConstraintViolation('__error_1__', null, [], null, '__property_path_1__', null),
            new ConstraintViolation('__error_2__', null, [], null, '__property_path_2__', null),
        ]);

        $exception = new ValidationFailedException('__argument_name__', $errors);

        self::assertSame(
            "Argument '__argument_name__->__property_path_1__' is invalid: __error_1__ and 1 more errors",
            $exception->getMessage(),
        );
        self::assertSame($errors, $exception->getErrors());
    }

    public function testEmptyErrorList(): void
    {
        $errors = new ConstraintViolationList([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot create %s with empty validation error list.',
            ValidationFailedException::class,
        ));
        $exception = new ValidationFailedException('__argument_name__', $errors);
        self::assertSame($errors, $exception->getErrors());
    }
}
