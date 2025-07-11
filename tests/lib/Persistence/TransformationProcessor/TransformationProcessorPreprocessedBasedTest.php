<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\TransformationProcessor;

use Ibexa\Core\Persistence;
use Ibexa\Core\Persistence\TransformationProcessor\PreprocessedBased;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * Test case for LocationHandlerTest.
 */
class TransformationProcessorPreprocessedBasedTest extends TestCase
{
    public function getProcessor()
    {
        $preprocessedRuleFiles = glob(
            dirname(__DIR__, 4) . '/src/lib/Resources/slug_converter/transformations/*.tr.result.php'
        );
        self::assertNotFalse($preprocessedRuleFiles, 'Failed to find preprocessed transformation files');

        return new PreprocessedBased(
            new Persistence\TransformationProcessor\PcreCompiler(new Persistence\Utf8Converter()),
            $preprocessedRuleFiles
        );
    }

    public function testSimpleNormalizationLowercase()
    {
        $processor = $this->getProcessor();

        self::assertSame(
            'hello world!',
            $processor->transform('Hello World!', ['ascii_lowercase'])
        );
    }

    public function testSimpleNormalizationUppercase()
    {
        $processor = $this->getProcessor();

        self::assertSame(
            'HELLO WORLD!',
            $processor->transform('Hello World!', ['ascii_uppercase'])
        );
    }

    /**
     * The main point of this test is, that it shows that all normalizations
     * available can be compiled without errors. The actual expectation is not
     * important.
     */
    public function testAllNormalizations()
    {
        $processor = $this->getProcessor();

        self::assertSame(
            'HELLO WORLD.',
            $processor->transform('Hello World!')
        );
    }
}
