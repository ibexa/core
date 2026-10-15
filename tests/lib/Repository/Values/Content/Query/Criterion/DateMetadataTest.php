<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\DateMetadata;
use PHPUnit\Framework\TestCase;

final class DateMetadataTest extends TestCase
{
    /**
     * @dataProvider provideValidConstructorArguments
     */
    public function testConstruction(
        string $target,
        string $operator,
        $value
    ): void {
        $criterion = new DateMetadata($target, $operator, $value);
        self::assertSame($target, $criterion->target);
    }

    /**
     * @return iterable<array{non-empty-string, string, int}>
     */
    public static function provideValidConstructorArguments(): iterable
    {
        $date = 0;
        $operator = '=';

        yield ['modified', $operator, $date];
        yield ['created', $operator, $date];
        yield ['published', $operator, $date];
        yield ['trashed', $operator, $date];
    }

    public function testExceptionOnInvalidTarget(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown DateMetadata target "foo". Expected one of: "modified", "created", "published", "trashed"');
        new DateMetadata('foo', '=', 0);
    }
}
