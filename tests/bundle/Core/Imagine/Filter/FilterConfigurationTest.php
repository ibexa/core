<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter;

use Ibexa\Bundle\Core\Imagine\Filter\FilterConfiguration;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\Filter\FilterConfiguration
 */
final class FilterConfigurationTest extends TestCase
{
    private ConfigResolverInterface & MockObject $configResolver;

    private FilterConfiguration $filterConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->filterConfiguration = new FilterConfiguration();
        $this->filterConfiguration->setConfigResolver($this->configResolver);
    }

    public function testGetOnlyImagineFilters(): void
    {
        $fooConfig = ['fooconfig'];
        $barConfig = ['barconfig'];
        $this->filterConfiguration->set('foo', $fooConfig);
        $this->filterConfiguration->set('bar', $barConfig);

        $this->configResolver
            ->expects(self::exactly(2))
            ->method('getParameter')
            ->with('image_variations')
            ->willReturn([]);

        self::assertSame($fooConfig, $this->filterConfiguration->get('foo'));
        self::assertSame($barConfig, $this->filterConfiguration->get('bar'));
    }

    public function testGetNoVariationInvalidImagineFilter(): void
    {
        $fooConfig = ['fooconfig'];
        $barConfig = ['barconfig'];
        $this->filterConfiguration->set('foo', $fooConfig);
        $this->filterConfiguration->set('bar', $barConfig);

        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_variations')
            ->willReturn([]);

        $this->expectException(RuntimeException::class);

        $this->filterConfiguration->get('foobar');
    }

    public function testGetWithVariationNullConfiguration(): void
    {
        $fooConfig = ['fooconfig'];
        $barConfig = ['barconfig'];
        $this->filterConfiguration->set('foo', $fooConfig);
        $this->filterConfiguration->set('bar', $barConfig);

        $variations = [
            'some_variation' => null,
        ];
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_variations')
            ->willReturn($variations);

        self::assertSame(
            [
                'cache' => 'ibexa',
                'data_loader' => 'ibexa',
                'reference' => null,
                'filters' => [],
                'post_processors' => [],
            ],
            $this->filterConfiguration->get('some_variation')
        );
    }

    public function testGetVariationNoReference(): void
    {
        $fooConfig = ['fooconfig'];
        $barConfig = ['barconfig'];
        $this->filterConfiguration->set('foo', $fooConfig);
        $this->filterConfiguration->set('bar', $barConfig);

        $filters = ['some_filter' => []];
        $variations = [
            'some_variation' => ['filters' => $filters],
        ];
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_variations')
            ->willReturn($variations);

        self::assertSame(
            [
                'cache' => 'ibexa',
                'data_loader' => 'ibexa',
                'reference' => null,
                'filters' => $filters,
                'post_processors' => [],
            ],
            $this->filterConfiguration->get('some_variation')
        );
    }

    public function testGetVariationWithReference(): void
    {
        $fooConfig = ['fooconfig'];
        $barConfig = ['barconfig'];
        $this->filterConfiguration->set('foo', $fooConfig);
        $this->filterConfiguration->set('bar', $barConfig);

        $filters = ['some_filter' => []];
        $reference = 'another_variation';
        $variations = [
            'some_variation' => ['filters' => $filters, 'reference' => $reference],
        ];
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_variations')
            ->willReturn($variations);

        self::assertSame(
            [
                'cache' => 'ibexa',
                'data_loader' => 'ibexa',
                'reference' => $reference,
                'filters' => $filters,
                'post_processors' => [],
            ],
            $this->filterConfiguration->get('some_variation')
        );
    }

    public function testGetVariationImagineFilters(): void
    {
        $filters = ['some_filter' => []];
        $imagineConfig = ['filters' => $filters];
        $this->filterConfiguration->set('some_variation', $imagineConfig);

        $reference = 'another_variation';
        $variations = [
            'some_variation' => ['reference' => $reference],
        ];
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_variations')
            ->willReturn($variations);

        self::assertSame(
            [
                'cache' => 'ibexa',
                'data_loader' => 'ibexa',
                'reference' => $reference,
                'filters' => $filters,
                'post_processors' => [],
            ],
            $this->filterConfiguration->get('some_variation')
        );
    }

    public function testGetVariationImagineOptions(): void
    {
        $imagineConfig = [
            'foo_option' => 'foo',
            'bar_option' => 'bar',
        ];
        $this->filterConfiguration->set('some_variation', $imagineConfig);

        $filters = ['some_filter' => []];
        $reference = 'another_variation';
        $variations = [
            'some_variation' => ['reference' => $reference, 'filters' => $filters],
        ];
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_variations')
            ->willReturn($variations);

        self::assertSame(
            [
                'cache' => 'ibexa',
                'data_loader' => 'ibexa',
                'reference' => $reference,
                'filters' => $filters,
                'post_processors' => [],
                'foo_option' => 'foo',
                'bar_option' => 'bar',
            ],
            $this->filterConfiguration->get('some_variation')
        );
    }

    public function testAll(): void
    {
        $fooConfig = ['fooconfig'];
        $barConfig = ['barconfig'];
        $this->filterConfiguration->set('foo', $fooConfig);
        $this->filterConfiguration->set('bar', $barConfig);
        $this->filterConfiguration->set('some_variation', []);

        $filters = ['some_filter' => []];
        $reference = 'another_variation';
        $eZVariationConfig = ['filters' => $filters, 'reference' => $reference];
        $variations = ['some_variation' => $eZVariationConfig];
        $this->configResolver
            ->expects(self::once())
            ->method('getParameter')
            ->with('image_variations')
            ->willReturn($variations);

        self::assertEquals(
            [
                'foo' => $fooConfig,
                'bar' => $barConfig,
                'some_variation' => $eZVariationConfig,
            ],
            $this->filterConfiguration->all()
        );
    }
}
