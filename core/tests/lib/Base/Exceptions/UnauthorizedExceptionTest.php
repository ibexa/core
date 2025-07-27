<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Base\Exceptions;

use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * @covers \Ibexa\Core\Base\Exceptions\UnauthorizedException
 */
final class UnauthorizedExceptionTest extends TestCase
{
    /**
     * @dataProvider getDataForTestConstructor
     */
    public function testConstructor(UnauthorizedException $exception, string $expectedMessage): void
    {
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @phpstan-return iterable<string, array{0: \Ibexa\Core\Base\Exceptions\UnauthorizedException, 1: string}>
     */
    public static function getDataForTestConstructor(): iterable
    {
        yield 'no properties (null)' => [
            new UnauthorizedException(
                'content',
                'read',
                null
            ),
            'The User does not have the \'read\' \'content\' permission',
        ];

        yield 'properties with an empty array of values' => [
            new UnauthorizedException(
                'content',
                'read',
                []
            ),
            'The User does not have the \'read\' \'content\' permission',
        ];

        yield 'properties with non-string values' => [
            new UnauthorizedException(
                'content',
                'read',
                [
                    'contentId' => 1,
                    'locationId' => 2,
                ]
            ),
            'The User does not have the \'read\' \'content\' permission with: contentId \'1\', locationId \'2\'',
        ];

        $stringableClass = new class() implements Stringable {
            public function __toString(): string
            {
                return 'bar';
            }
        };
        yield 'properties with stringable value' => [
            new UnauthorizedException(
                'content',
                'read',
                [
                    'foo' => $stringableClass,
                ]
            ),
            'The User does not have the \'read\' \'content\' permission with: foo \'bar\'',
        ];

        yield 'properties with null value' => [
            new UnauthorizedException('content', 'versionread', ['foo' => null]),
            'The User does not have the \'versionread\' \'content\' permission with: foo \'\'',
        ];
    }
}
