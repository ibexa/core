<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\TransformationProcessor;

use Ibexa\Core\Persistence;
use Ibexa\Core\Persistence\TransformationProcessor\DefinitionBased;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * Test case for LocationHandlerTest.
 */
class TransformationProcessorDefinitionBasedTest extends TestCase
{
    public function getProcessor(): DefinitionBased
    {
        return new DefinitionBased(
            new DefinitionBased\Parser(),
            new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter()),
            glob(__DIR__ . '/_fixtures/transformations/*.tr')
        );
    }

    public function testSimpleNormalizationLowercase(): void
    {
        $processor = $this->getProcessor();

        self::assertSame(
            'hello world!',
            $processor->transform('Hello World!', ['ascii_lowercase'])
        );
    }

    public function testSimpleNormalizationUppercase(): void
    {
        $processor = $this->getProcessor();

        self::assertSame(
            'HELLO WORLD!',
            $processor->transform('Hello World!', ['ascii_uppercase'])
        );
    }

    public function testApplyAllLowercaseNormalizations(): void
    {
        $processor = $this->getProcessor();

        self::assertSame(
            'hello world!',
            $processor->transformByGroup('Hello World!', 'lowercase')
        );
    }

    /**
     * The main point of this test is, that it shows that all normalizations
     * available can be compiled without errors. The actual expectation is not
     * important.
     */
    public function testAllNormalizations(): void
    {
        $processor = $this->getProcessor();

        self::assertSame(
            'HELLO WORLD.',
            $processor->transform('Hello World!')
        );
    }
}
