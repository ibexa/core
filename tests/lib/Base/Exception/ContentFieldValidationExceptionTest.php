<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Base\Exception;

use Ibexa\Core\Base\Exceptions\ContentFieldValidationException;
use Ibexa\Core\FieldType\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Base\Exceptions\ContentFieldValidationException
 */
final class ContentFieldValidationExceptionTest extends TestCase
{
    public function testGetFieldErrors(): ContentFieldValidationException
    {
        $errors = [
            123 => [
                'eng-GB' => [
                    new ValidationError('error 1'), new ValidationError('error 2'),
                ],
                'pol-PL' => [
                    new ValidationError('error 1'), new ValidationError('error 2'),
                ],
            ],
            456 => [
                'pol-PL' => [
                    new ValidationError('error 3'), new ValidationError('error 4'),
                ],
                'eng-GB' => [
                    new ValidationError('error 3'), new ValidationError('error 4'),
                ],
            ],
        ];

        $exception = new ContentFieldValidationException($errors);

        self::assertSame($errors, $exception->getFieldErrors());

        return $exception;
    }

    /**
     * @depends testGetFieldErrors
     */
    public function testCreateNewWithMultiline(ContentFieldValidationException $exception): void
    {
        $newException = ContentFieldValidationException::createNewWithMultiline(
            $exception->getFieldErrors(),
            'My Content'
        );

        $expectedExceptionMessage = <<<MSG
        Content "My Content" fields did not validate: 
        - error 1
        - error 2
        - error 1
        - error 2
        - error 3
        - error 4
        - error 3
        - error 4
        MSG;

        self::assertSame($expectedExceptionMessage, $newException->getMessage());
    }
}
