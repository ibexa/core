<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values\Translation;

use Ibexa\Contracts\Core\Repository\Values\Translation\Message;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\Translation\Message
 */
final class MessageTest extends TestCase
{
    /**
     * @dataProvider getDataForTestMessage
     *
     * @param array<string, scalar|null> $values
     */
    public function testStringable(
        string $message,
        array $values,
        string $expectedString
    ): void {
        self::assertSame($expectedString, (string)new Message($message, $values));
    }

    /**
     * @dataProvider getDataForTestMessage
     *
     * @param array<string, scalar|null> $values
     */
    public function testGetters(string $message, array $values): void
    {
        $translation = new Message($message, $values);

        self::assertSame($message, $translation->getMessageTemplate());
        self::assertSame($values, $translation->getValues());
    }

    /**
     * @return iterable<string, array{string, array<string, scalar|null>, string}>
     */
    public static function getDataForTestMessage(): iterable
    {
        yield 'message with substitution values' => [
            'Anna has some oranges in %object%',
            [
                '%object%' => 'a basket',
            ],
            'Anna has some oranges in a basket',
        ];

        yield 'message with multiple substitution values' => [
            '%first_name% has some data in %storage_type%',
            [
                '%first_name%' => 'Anna',
                '%storage_type%' => 'her database',
            ],
            'Anna has some data in her database',
        ];

        yield 'message with no substitution values' => [
            'This value is not correct',
            [],
            'This value is not correct',
        ];
    }
}
