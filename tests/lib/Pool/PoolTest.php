<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Pool;

use Ibexa\Contracts\Core\Pool\Pool;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PoolTest extends TestCase
{
    private stdClass $foo;

    private stdClass $bar;

    /** @var Pool<stdClass> */
    private Pool $pool;

    protected function setUp(): void
    {
        $this->foo = new stdClass();
        $this->bar = new stdClass();
        $entries = [
            'foo' => $this->foo,
            'bar' => $this->bar,
        ];

        $this->pool = new Pool(
            stdClass::class,
            $entries
        );
    }

    public function testHas(): void
    {
        self::assertTrue($this->pool->has('foo'));
        self::assertFalse($this->pool->has('baz'));
    }

    public function testGet(): void
    {
        self::assertSame($this->foo, $this->pool->get('foo'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Argument '%s' is invalid: Could not find stdClass for 'baz'. Valid values are: 'foo', 'bar'",
                '$alias'
            )
        );

        $this->pool->get('baz');
    }

    public function testGetEntries(): void
    {
        self::assertSame(
            [
                'foo' => $this->foo,
                'bar' => $this->bar,
            ],
            $this->pool->getEntries()
        );
    }
}
