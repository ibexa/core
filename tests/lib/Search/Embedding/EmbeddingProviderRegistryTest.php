<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Embedding;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderInterface;
use Ibexa\Core\Search\Embedding\EmbeddingProviderRegistry;
use PHPUnit\Framework\TestCase;

final class EmbeddingProviderRegistryTest extends TestCase
{
    public function testHasEmbeddingProvider(): void
    {
        $registry = new EmbeddingProviderRegistry([
            'existing' => $this->createMock(EmbeddingProviderInterface::class),
        ]);

        self::assertTrue($registry->hasEmbeddingProvider('existing'));
        self::assertFalse($registry->hasEmbeddingProvider('non-existing'));
    }

    public function testGetEmbeddingProvider(): void
    {
        $expectedEmbeddingProvider = $this->createMock(EmbeddingProviderInterface::class);

        $registry = new EmbeddingProviderRegistry([
            'example' => $expectedEmbeddingProvider,
        ]);

        self::assertSame($expectedEmbeddingProvider, $registry->getEmbeddingProvider('example'));
    }

    public function testGetEmbeddingProviderThrowsInvalidArgumentException(): void
    {
        $message = "Argument 'embedding_provider' is invalid: Could not find "
            . "Ibexa\Contracts\Core\Search\Embedding\EmbeddingProviderInterface for 'non-existing' embedding provider.";

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $registry = new EmbeddingProviderRegistry([/* Empty registry */]);
        $registry->getEmbeddingProvider('non-existing');
    }

    public function testGetEmbeddingProviders(): void
    {
        $embeddingProviderA = $this->createMock(EmbeddingProviderInterface::class);
        $embeddingProviderB = $this->createMock(EmbeddingProviderInterface::class);

        $registry = new EmbeddingProviderRegistry([
            'existingA' => $embeddingProviderA,
            'existingB' => $embeddingProviderB,
        ]);

        $embeddingProviders = $registry->getEmbeddingProviders();

        $this->assertIsArray(
            $embeddingProviders,
            'getProviders() should return an array of embedding providers'
        );

        self::assertSame(
            [
                'existingA' => $embeddingProviderA,
                'existingB' => $embeddingProviderB,
            ],
            $embeddingProviders
        );
    }
}
