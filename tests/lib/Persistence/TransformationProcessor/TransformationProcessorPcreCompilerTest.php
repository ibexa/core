<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\TransformationProcessor;

use Ibexa\Core\Persistence;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * Test case for LocationHandlerTest.
 */
class TransformationProcessorPcreCompilerTest extends TestCase
{
    /**
     * Applies the transformations.
     *
     * @param array $transformations
     * @param string $string
     *
     * @return string
     */
    protected function applyTransformations(
        array $transformations,
        $string
    ) {
        foreach ($transformations as $rules) {
            foreach ($rules as $rule) {
                $string = preg_replace_callback($rule['regexp'], $rule['callback'], $string);
            }
        }

        return $string;
    }

    public function testCompileMap()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "map_test:\n" .
                'U+00e4 = "ae"'
            )
        );

        self::assertSame(
            'aeöü',
            $this->applyTransformations($rules, 'äöü')
        );
    }

    public function testCompileMapRemove()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "map_test:\n" .
                'U+00e4 = remove'
            )
        );

        self::assertSame(
            'öü',
            $this->applyTransformations($rules, 'äöü')
        );
    }

    public function testCompileMapKeep()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "map_test:\n" .
                'U+00e4 = keep'
            )
        );

        self::assertSame(
            'äöü',
            $this->applyTransformations($rules, 'äöü')
        );
    }

    public function testCompileMapAscii()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "map_test:\n" .
                'U+00e4 = 41'
            )
        );

        self::assertSame(
            'Aöü',
            $this->applyTransformations($rules, 'äöü')
        );
    }

    public function testCompileMapUnicode()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "map_test:\n" .
                'U+00e4 = U+00e5'
            )
        );

        self::assertSame(
            'åöü',
            $this->applyTransformations($rules, 'äöü')
        );
    }

    public function testCompileReplace()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "replace_test:\n" .
                'U+00e0 - U+00e6 = "a"'
            )
        );

        self::assertSame(
            'aaaaaaaçè',
            $this->applyTransformations($rules, 'àáâãäåæçè')
        );
    }

    public function testCompileTranspose()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "transpose_test:\n" .
                'U+00e0 - U+00e6 - 02'
            )
        );

        self::assertSame(
            'Þßàáâãäçè',
            $this->applyTransformations($rules, 'àáâãäåæçè')
        );
    }

    public function testCompileTransposeAsciiLowercase()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "ascii_lowercase:\n" .
                'U+0041 - U+005A + 20'
            )
        );

        self::assertSame(
            'hello world',
            $this->applyTransformations($rules, 'Hello World')
        );
    }

    public function testCompileTransposePlus()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "transpose_test:\n" .
                'U+00e0 - U+00e6 + 02'
            )
        );

        self::assertSame(
            'âãäåæçèçè',
            $this->applyTransformations($rules, 'àáâãäåæçè')
        );
    }

    public function testCompileModuloTranspose()
    {
        $parser = new Persistence\TransformationProcessor\DefinitionBased\Parser(self::getInstallationDir());
        $compiler = new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter());

        $rules = $compiler->compile(
            $parser->parseString(
                "transpose_modulo_test:\n" .
                'U+00e0 - U+00e6 % 02 - 01'
            )
        );

        self::assertSame(
            'ßááããååçè',
            $this->applyTransformations($rules, 'àáâãäåæçè')
        );
    }
}
