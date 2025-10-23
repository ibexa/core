<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Base\Exceptions;

use Ibexa\Core\Base\Exceptions\ContentFieldValidationException;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Tests\Core\Search\TestCase;

/**
 * @covers \Ibexa\Core\Base\Exceptions\ContentFieldValidationException
 */
final class ContentFieldValidationExceptionTest extends TestCase
{
    /**
     * @see ContentFieldValidationException::MAX_MESSAGES_NUMBER
     */
    private const int MAX_MESSAGES_NUMBER = 32;

    public function testTranslatableMessageValidationErrorLimit(): void
    {
        $errors = [];
        for ($fieldId = 1; $fieldId <= self::MAX_MESSAGES_NUMBER + 1; ++$fieldId) {
            $errors[$fieldId] = [
                'eng-GB' => [new ValidationError("Field $fieldId error message")],
            ];
        }
        $exception = ContentFieldValidationException::createNewWithMultiline($errors);
        self::assertStringEndsWith(
            sprintf('- Limit of %d validation errors has been exceeded.', self::MAX_MESSAGES_NUMBER),
            $exception->getMessage()
        );
    }
}
