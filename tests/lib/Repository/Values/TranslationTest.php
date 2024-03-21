<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values;

use Ibexa\Contracts\Core\Repository\Values\Translation;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

final class TranslationTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testMissingToStringMethodTriggersDeprecation(): void
    {
        $instance = $this
            ->getMockBuilder(Translation::class)
            ->onlyMethods([])
            ->getMock()
        ;

        $this->expectDeprecation(
            'Since ibexa/core 4.6: Not overriding `Ibexa\Contracts\Core\Repository\Values\Translation::__toString(): string` ' .
            sprintf('method in `%s` is deprecated, will cause fatal error in 5.0.', get_class($instance))
        );

        self::assertEmpty((string)$instance);
    }
}
