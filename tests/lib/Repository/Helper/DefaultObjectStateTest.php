<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Helper;

use Ibexa\Contracts\Core\Persistence\Content\ObjectState;
use Ibexa\Core\Repository\Helper\DefaultObjectState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultObjectState::class)]
final class DefaultObjectStateTest extends TestCase
{
    public function testReturnsLowestPriorityStateRegardlessOfOrder(): void
    {
        $default = new ObjectState(['id' => 1, 'priority' => 0]);

        self::assertSame(
            $default,
            DefaultObjectState::fromGroupStates([
                new ObjectState(['id' => 3, 'priority' => 2]),
                $default,
                new ObjectState(['id' => 2, 'priority' => 1]),
            ])
        );
    }

    public function testReturnsNullForGroupWithoutStates(): void
    {
        self::assertNull(DefaultObjectState::fromGroupStates([]));
    }
}
