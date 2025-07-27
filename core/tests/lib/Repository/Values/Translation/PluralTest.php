<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values\Translation;

use Ibexa\Contracts\Core\Repository\Values\Translation\Plural;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\Translation\Plural
 */
final class PluralTest extends TestCase
{
    /**
     * @dataProvider getDataForTestStringable
     */
    public function testStringable(Plural $message, string $expectedString): void
    {
        self::assertSame($expectedString, (string)$message);
    }

    /**
     * @return array<string, array{\Ibexa\Contracts\Core\Repository\Values\Translation\Plural, string}>
     */
    public static function getDataForTestStringable(): iterable
    {
        yield 'singular form' => [
            new Plural(
                'John has %apple_count% apple',
                'John has %apple_count% apples',
                [
                    '%apple_count%' => 1,
                ]
            ),
            'John has 1 apple',
        ];

        yield 'plural form' => [
            new Plural(
                'John has %apple_count% apple',
                'John has %apple_count% apples',
                [
                    '%apple_count%' => 2,
                ]
            ),
            'John has 2 apples',
        ];

        yield 'no substitution values' => [
            new Plural(
                'John has some apples',
                'John has a lot of apples',
                []
            ),
            'John has a lot of apples',
        ];
    }
}
