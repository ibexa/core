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
     * @dataProvider getDataForTestStringable
     */
    public function testStringable(Message $message, string $expectedString): void
    {
        self::assertSame($expectedString, (string)$message);
    }

    /**
     * @return iterable<string, array{\Ibexa\Contracts\Core\Repository\Values\Translation\Message, string}>
     */
    public static function getDataForTestStringable(): iterable
    {
        yield 'message with substitution values' => [
            new Message(
                'Anna has some oranges in %object%',
                [
                    '%object%' => 'a basket',
                ]
            ),
            'Anna has some oranges in a basket',
        ];

        yield 'message with multiple substitution values' => [
            new Message(
                '%first_name% has some data in %storage_type%',
                [
                    '%first_name%' => 'Anna',
                    '%storage_type%' => 'her database',
                ]
            ),
            'Anna has some data in her database',
        ];

        yield 'message with no substitution values' => [
            new Message(
                'This value is not correct',
                []
            ),
            'This value is not correct',
        ];
    }
}
