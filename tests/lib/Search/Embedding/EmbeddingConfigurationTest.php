<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Embedding;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Search\Embedding\EmbeddingConfiguration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmbeddingConfigurationTest extends TestCase
{
    private const MODELS = [
        'text-embedding-3-small' => ['name' => 'text-embedding-3-small', 'dimensions' => 1536, 'field_suffix' => '3small', 'embedding_provider' => 'ibexa_openai'],
        'text-embedding-3-large' => ['name' => 'text-embedding-3-large', 'dimensions' => 3072, 'field_suffix' => '3large', 'embedding_provider' => 'ibexa_openai'],
        'text-embedding-ada-002' => ['name' => 'text-embedding-ada-002', 'dimensions' => 1536, 'field_suffix' => 'ada002', 'embedding_provider' => 'ibexa_openai'],
    ];

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface&\PHPUnit\Framework\MockObject\MockObject */
    private ConfigResolverInterface $configResolver;

    private EmbeddingConfiguration $config;

    protected function setUp(): void
    {
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->config = new EmbeddingConfiguration(
            $this->configResolver
        );
    }

    public function testGetDefaultEmbeddingModel(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['default_embedding_model', null, null, 'text-embedding-ada-002'],
                ['embedding_models', null, null, self::MODELS],
            ]);

        $this->assertSame(
            ['name' => 'text-embedding-ada-002', 'dimensions' => 1536, 'field_suffix' => 'ada002', 'embedding_provider' => 'ibexa_openai'],
            $this->config->getDefaultEmbeddingModel()
        );
    }

    public function testGetEmbeddingModelIdentifiers(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['default_embedding_model', null, null, 'text-embedding-ada-002'],
                ['embedding_models', null, null, self::MODELS],
            ]);

        $this->assertSame(
            ['text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002'],
            $this->config->getEmbeddingModelIdentifiers()
        );
    }

    public function testGetEmbeddingModels(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->with('embedding_models')
            ->willReturn(self::MODELS);

        $this->assertSame(self::MODELS, $this->config->getEmbeddingModels());
        $this->assertSame(
            ['text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002'],
            $this->config->getEmbeddingModelIdentifiers()
        );
    }

    public function testGetEmbeddingModel(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->with('embedding_models')
            ->willReturn(self::MODELS);

        $this->assertSame(
            ['name' => 'text-embedding-ada-002', 'dimensions' => 1536, 'field_suffix' => 'ada002', 'embedding_provider' => 'ibexa_openai'],
            $this->config->getEmbeddingModel('text-embedding-ada-002')
        );
    }

    public function testGetEmbeddingModelWillThrowException(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->with('embedding_models')
            ->willReturn(self::MODELS);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Embedding model "non-existing-model" is not configured.');

        $this->config->getEmbeddingModel('non-existing-model');
    }

    public function testGetDefaultEmbeddingModelIdentifier(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->with('default_embedding_model')
            ->willReturn('text-embedding-ada-002');

        $this->assertSame('text-embedding-ada-002', $this->config->getDefaultEmbeddingModelIdentifier());
    }

    public function testGetDefaultEmbeddingProvider(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['default_embedding_model', null, null, 'text-embedding-ada-002'],
                ['embedding_models', null, null, self::MODELS],
            ]);

        $this->assertSame('ibexa_openai', $this->config->getDefaultEmbeddingProvider());
    }

    public function getDefaultEmbeddingModelFieldSuffix(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['default_embedding_model', null, null, 'text-embedding-ada-002'],
                ['embedding_models', null, null, self::MODELS],
            ]);

        $this->assertSame('ada002', $this->config->getDefaultEmbeddingModelFieldSuffix());
    }
}
