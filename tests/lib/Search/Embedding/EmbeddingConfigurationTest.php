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

    public function testGetDefaultModel(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['default_embedding_model', null, null, 'text-embedding-ada-002'],
                ['embedding_models', null, null, self::MODELS],
            ]);

        self::assertSame(
            ['name' => 'text-embedding-ada-002', 'dimensions' => 1536, 'field_suffix' => 'ada002', 'embedding_provider' => 'ibexa_openai'],
            $this->config->getDefaultModel()
        );
    }

    public function testGetModelIdentifiers(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['default_embedding_model', null, null, 'text-embedding-ada-002'],
                ['embedding_models', null, null, self::MODELS],
            ]);

        self::assertSame(
            ['text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002'],
            $this->config->getModelIdentifiers()
        );
    }

    public function testGetModels(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->with('embedding_models')
            ->willReturn(self::MODELS);

        self::assertSame(self::MODELS, $this->config->getModels());
        self::assertSame(
            ['text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002'],
            $this->config->getModelIdentifiers()
        );
    }

    public function testGetModel(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->with('embedding_models')
            ->willReturn(self::MODELS);

        self::assertSame(
            ['name' => 'text-embedding-ada-002', 'dimensions' => 1536, 'field_suffix' => 'ada002', 'embedding_provider' => 'ibexa_openai'],
            $this->config->getModel('text-embedding-ada-002')
        );
    }

    public function testGetModelWillThrowException(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->with('embedding_models')
            ->willReturn(self::MODELS);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding model "non-existing-model" is not configured.');

        $this->config->getModel('non-existing-model');
    }

    public function testGetDefaultModelIdentifier(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->with('default_embedding_model')
            ->willReturn('text-embedding-ada-002');

        self::assertSame('text-embedding-ada-002', $this->config->getDefaultModelIdentifier());
    }

    public function testGetDefaultProvider(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['default_embedding_model', null, null, 'text-embedding-ada-002'],
                ['embedding_models', null, null, self::MODELS],
            ]);

        self::assertSame('ibexa_openai', $this->config->getDefaultProvider());
    }

    public function testGetDefaultModelFieldSuffix(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap([
                ['default_embedding_model', null, null, 'text-embedding-ada-002'],
                ['embedding_models', null, null, self::MODELS],
            ]);

        self::assertSame('ada002', $this->config->getDefaultModelFieldSuffix());
    }
}
