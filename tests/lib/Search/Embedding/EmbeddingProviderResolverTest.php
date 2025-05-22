<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Embedding;

use Ibexa\Contracts\Core\Search\Embedding\EmbeddingConfigurationInterface;
use Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderInterface;
use Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderRegistryInterface;
use Ibexa\Core\Search\Embedding\EmbeddingProviderResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EmbeddingProviderResolverTest extends TestCase
{
    /** @var \Ibexa\Contracts\Core\Search\Embedding\EmbeddingConfigurationInterface&\PHPUnit\Framework\MockObject\MockObject */
    private EmbeddingConfigurationInterface $configuration;

    /** @var \Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderRegistryInterface&\PHPUnit\Framework\MockObject\MockObject */
    private EmbeddingProviderRegistryInterface $registry;

    private EmbeddingProviderResolver $resolver;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(EmbeddingConfigurationInterface::class);
        $this->registry = $this->createMock(EmbeddingProviderRegistryInterface::class);
        $this->resolver = new EmbeddingProviderResolver(
            $this->configuration,
            $this->registry
        );
    }

    public function testResolveReturnsProviderWhenAvailable(): void
    {
        $embeddingProviderIdentifier = 'ibexa_openai';
        $mockProvider = $this->createMock(EmbeddingProviderInterface::class);

        $this->configuration
            ->method('getDefaultEmbeddingProvider')
            ->willReturn($embeddingProviderIdentifier);

        $this->registry
            ->method('hasEmbeddingProvider')
            ->with($embeddingProviderIdentifier)
            ->willReturn(true);

        $this->registry
            ->method('getEmbeddingProvider')
            ->with($embeddingProviderIdentifier)
            ->willReturn($mockProvider);

        $resolved = $this->resolver->resolve();

        $this->assertSame($mockProvider, $resolved);
    }

    public function testResolveThrowsWhenProviderMissing(): void
    {
        $embeddingProviderIdentifier = 'foo';

        $this->configuration
            ->method('getDefaultEmbeddingProvider')
            ->willReturn($embeddingProviderIdentifier);

        $this->registry
            ->method('hasEmbeddingProvider')
            ->with($embeddingProviderIdentifier)
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf('No embedding provider registered for identifier "%s".', $embeddingProviderIdentifier)
        );

        $this->resolver->resolve();
    }
}
