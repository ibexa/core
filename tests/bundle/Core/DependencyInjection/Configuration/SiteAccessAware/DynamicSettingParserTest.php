<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\DynamicSettingParser;
use PHPUnit\Framework\TestCase;

class DynamicSettingParserTest extends TestCase
{
    /**
     * @dataProvider isDynamicSettingProvider
     */
    public function testIsDynamicSetting(string $setting, bool $expected): void
    {
        $parser = new DynamicSettingParser();
        self::assertSame($expected, $parser->isDynamicSetting($setting));
    }

    public function isDynamicSettingProvider(): array
    {
        return [
            ['foo', false],
            ['%foo%', false],
            ['$foo', false],
            ['foo$', false],
            ['$foo$', true],
            ['$foo.bar$', true],
            ['$foo_bar$', true],
            ['$foo.bar$', true],
            ['$foo;ba_bar$', true],
            ['$foo;babar.elephant$', true],
            ['$foo;babar;elephant$', true],
            ['$foo;bar;baz_biz$', true],
            ['$foo$/$bar$', false],
        ];
    }

    public function testParseDynamicSettingFail(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $parser = new DynamicSettingParser();
        $parser->parseDynamicSetting('$foo;bar;baz;biz$');
    }

    /**
     * @dataProvider parseDynamicSettingProvider
     */
    public function testParseDynamicSetting(string $setting, array $expected): void
    {
        $parser = new DynamicSettingParser();
        self::assertSame($expected, $parser->parseDynamicSetting($setting));
    }

    public function parseDynamicSettingProvider(): array
    {
        return [
            [
                '$foo$',
                [
                    'param' => 'foo',
                    'namespace' => null,
                    'scope' => null,
                ],
            ],
            [
                '$foo.bar$',
                [
                    'param' => 'foo.bar',
                    'namespace' => null,
                    'scope' => null,
                ],
            ],
            [
                '$foo;bar$',
                [
                    'param' => 'foo',
                    'namespace' => 'bar',
                    'scope' => null,
                ],
            ],
            [
                '$foo;ba_bar;biz$',
                [
                    'param' => 'foo',
                    'namespace' => 'ba_bar',
                    'scope' => 'biz',
                ],
            ],
        ];
    }
}
