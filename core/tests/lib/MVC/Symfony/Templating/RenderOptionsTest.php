<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Templating;

use Ibexa\Core\MVC\Symfony\Templating\RenderOptions;
use PHPUnit\Framework\TestCase;

class RenderOptionsTest extends TestCase
{
    public function testInitialOptions(): void
    {
        $renderOptions = new RenderOptions([
            'a' => 'value_a',
            'b' => null,
        ]);

        self::assertTrue($renderOptions->has('a'));
        self::assertSame('value_a', $renderOptions->get('a'));
        self::assertFalse($renderOptions->has('b'));
        self::assertSame([
            'a' => 'value_a',
            'b' => null,
        ], $renderOptions->all());
    }

    public function testSettingOptions(): void
    {
        $renderOptions = new RenderOptions();

        $renderOptions->set('a', 'value_a');
        self::assertTrue($renderOptions->has('a'));
        self::assertSame('value_a', $renderOptions->get('a'));

        self::assertTrue($renderOptions->has('a'));
        $renderOptions->set('a', 'different_value_a');
        self::assertSame('different_value_a', $renderOptions->get('a'));

        self::assertFalse($renderOptions->has('b'));
        $renderOptions->set('b', null);
        self::assertFalse($renderOptions->has('b'));
    }

    public function testGettingDefaultOptions(): void
    {
        $renderOptions = new RenderOptions([
            'a' => null,
            'b' => 'default_value_b',
        ]);

        self::assertFalse($renderOptions->has('a'));
        self::assertSame('some_default_value', $renderOptions->get('a', 'some_default_value'));

        self::assertTrue($renderOptions->has('b'));
        self::assertSame('default_value_b', $renderOptions->get('b', 'other_default_value'));

        self::assertFalse($renderOptions->has('c'));
        self::assertSame('default_value_c', $renderOptions->get('c', 'default_value_c'));
    }

    public function testUnsettingOptions(): void
    {
        $renderOptions = new RenderOptions([
            'a' => 'value_a',
            'b' => 'value_b',
            'c' => 'value_c',
        ]);

        $renderOptions->set('a', null);
        self::assertFalse($renderOptions->has('a'));

        $renderOptions->remove('b');
        self::assertFalse($renderOptions->has('b'));

        self::assertTrue($renderOptions->has('c'));
    }
}
