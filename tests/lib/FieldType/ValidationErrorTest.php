<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Core\FieldType\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\FieldType\ValidationError
 */
final class ValidationErrorTest extends TestCase
{
    /**
     * @dataProvider getDataForTestGetTranslatableMessage
     */
    public function testGetTranslatableMessage(ValidationError $validationError, string $expectedMessage): void
    {
        self::assertSame($expectedMessage, (string)$validationError->getTranslatableMessage());
    }

    /**
     * @phpstan-return iterable<string, array{0: \Ibexa\Core\FieldType\ValidationError, 1: string}>
     */
    public static function getDataForTestGetTranslatableMessage(): iterable
    {
        $validatorIdentifier = 'FileSizeValidator';
        yield "$validatorIdentifier maxFileSize not set" => [
            new ValidationError(
                'Validator %validator% expects parameter %parameter% to be set.',
                null,
                [
                    '%validator%' => 'FileSizeValidator',
                    '%parameter%' => 'maxFileSize',
                ],
                "[{$validatorIdentifier}][maxFileSize]"
            ),
            'Validator FileSizeValidator expects parameter maxFileSize to be set.',
        ];

        yield "$validatorIdentifier with non-integer maxFileSize" => [
            new ValidationError(
                'Validator %validator% expects parameter %parameter% to be of %type% type.',
                null,
                [
                    '%validator%' => $validatorIdentifier,
                    '%parameter%' => 'maxFileSize',
                    '%type%' => 'integer',
                ],
                "[$validatorIdentifier][maxFileSize]",
            ),
            'Validator FileSizeValidator expects parameter maxFileSize to be of integer type.',
        ];

        $validatorIdentifier = 'UnknownValidator';
        yield $validatorIdentifier => [
            new ValidationError(
                "Validator '%validator%' is unknown",
                null,
                [
                    '%validator%' => $validatorIdentifier,
                ],
                "[$validatorIdentifier]"
            ),
            "Validator '$validatorIdentifier' is unknown",
        ];

        yield 'validation error with null value' => [
            new ValidationError(
                "Foo '%bar%' error",
                null,
                [
                    '%bar%' => null,
                ],
            ),
            "Foo '' error",
        ];
    }
}
