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
     * @dataProvider getDataForTestPlural
     *
     * @param array<string, scalar|null> $values
     */
    public function testStringable(
        string $singular,
        string $plural,
        array $values,
        string $expectedString
    ): void {
        self::assertSame($expectedString, (string)new Plural($singular, $plural, $values));
    }

    /**
     * @dataProvider getDataForTestPlural
     *
     * @param array<string, scalar|null> $values
     */
    public function testGetters(
        string $singular,
        string $plural,
        array $values
    ): void {
        $translation = new Plural($singular, $plural, $values);

        self::assertSame($plural, $translation->getMessageTemplate());
        self::assertSame($values, $translation->getValues());
    }

    /**
     * @return iterable<string, array{string, string, array<string, scalar|null>, string}>
     */
    public static function getDataForTestPlural(): iterable
    {
        yield 'singular form' => [
            'John has %apple_count% apple',
            'John has %apple_count% apples',
            [
                '%apple_count%' => 1,
            ],
            'John has 1 apple',
        ];

        yield 'plural form' => [
            'John has %apple_count% apple',
            'John has %apple_count% apples',
            [
                '%apple_count%' => 2,
            ],
            'John has 2 apples',
        ];

        yield 'no substitution values' => [
            'John has some apples',
            'John has a lot of apples',
            [],
            'John has a lot of apples',
        ];
    }
}
