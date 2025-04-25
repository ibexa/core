<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\ComplexSettings;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ComplexSettings\ComplexSettingParser;
use PHPUnit\Framework\TestCase;

class ComplexSettingParserTest extends TestCase
{
    /** @var \Ibexa\Bundle\Core\DependencyInjection\Configuration\ComplexSettings\ComplexSettingParser */
    private ComplexSettingParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ComplexSettingParser();
    }

    /**
     * @dataProvider provideSettings
     */
    public function testContainsDynamicSettings(string $setting, array $expected): void
    {
        self::assertEquals($expected[0], $this->parser->containsDynamicSettings($setting), 'string');
    }

    /**
     * @dataProvider provideSettings
     */
    public function testParseComplexSetting(string $setting, array $expected): void
    {
        self::assertEquals($expected[1], $this->parser->parseComplexSetting($setting), 'string');
    }

    public function provideSettings(): array
    {
        // array( setting, array( isDynamicSetting, containsDynamicSettings ) )
        return [
            [
                '%container_var%',
                [false, []],
            ],
            [
                '$somestring',
                [false, []],
            ],
            [
                '$setting$',
                [true, ['$setting$']],
            ],
            [
                '$setting;scope$',
                [true, ['$setting;scope$']],
            ],
            [
                '$setting;namespace;scope$',
                [true, ['$setting;namespace;scope$']],
            ],
            [
                'a_string_before$setting;namespace;scope$',
                [true, ['$setting;namespace;scope$']],
            ],
            [
                '$setting;namespace;scope$a_string_after',
                [true, ['$setting;namespace;scope$']],
            ],
            [
                'a_string_before$setting;namespace;scope$a_string_after',
                [true, ['$setting;namespace;scope$']],
            ],
            [
                '$setting;one$somethingelse$setting;two$',
                [true, ['$setting;one$', '$setting;two$']],
            ],
        ];
    }
}
