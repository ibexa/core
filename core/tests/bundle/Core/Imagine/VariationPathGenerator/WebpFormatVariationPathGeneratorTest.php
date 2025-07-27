<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\VariationPathGenerator;

use Ibexa\Bundle\Core\Imagine\Filter\FilterConfiguration;
use Ibexa\Bundle\Core\Imagine\VariationPathGenerator\WebpFormatVariationPathGenerator;
use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class WebpFormatVariationPathGeneratorTest extends TestCase
{
    private VariationPathGenerator&MockObject $innerVariationPathGenerator;

    /** @var \Ibexa\Bundle\Core\Imagine\Filter\FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private FilterConfiguration $filterConfiguration;

    protected function setUp(): void
    {
        $this->innerVariationPathGenerator = $this->createMock(VariationPathGenerator::class);
        $this->filterConfiguration = $this->createMock(FilterConfiguration::class);
    }

    public function testGetVariationPath(): void
    {
        $this->innerVariationPathGenerator
            ->method('getVariationPath')
            ->willReturn('tmp/variation/test.jpeg');

        $this->filterConfiguration
            ->method('get')
            ->with('large')
            ->willReturn([
                'format' => 'webp',
            ]);

        $generator = new WebpFormatVariationPathGenerator(
            $this->innerVariationPathGenerator,
            $this->filterConfiguration
        );

        self::assertEquals(
            'tmp/variation/test.jpeg.webp',
            $generator->getVariationPath('tmp/original/test.jpeg', 'large')
        );
    }

    public function testGetVariationNonWebpVariation(): void
    {
        $this->innerVariationPathGenerator
            ->method('getVariationPath')
            ->willReturn('tmp/variation/test.jpeg');

        $this->filterConfiguration
            ->method('get')
            ->with('large')
            ->willReturn([
                'format' => 'jpeg',
            ]);

        $generator = new WebpFormatVariationPathGenerator(
            $this->innerVariationPathGenerator,
            $this->filterConfiguration
        );

        self::assertEquals(
            'tmp/variation/test.jpeg',
            $generator->getVariationPath('tmp/original/test.jpeg', 'large')
        );
    }
}
