<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Search\FieldType;

use Ibexa\Contracts\Core\Search\Embedding\EmbeddingConfigurationInterface;
use Ibexa\Contracts\Core\Search\FieldType\EmbeddingFieldFactory;
use PHPUnit\Framework\TestCase;

final class EmbeddingFieldFactoryTest extends TestCase
{
    public function testCreateUsesConfigSuffix(): void
    {
        $suffix = 'model_123';
        $config = $this->createMock(EmbeddingConfigurationInterface::class);
        $config
            ->expects($this->once())
            ->method('getDefaultEmbeddingModelFieldSuffix')
            ->willReturn($suffix);

        $factory = new EmbeddingFieldFactory($config);

        $field = $factory->create();

        $this->assertSame(
            'ibexa_dense_vector_model_123',
            $field->getType(),
            'Factory should prepend "ibexa_dense_vector_" to the suffix from the config'
        );
    }

    public function testCreateWithCustomType(): void
    {
        $config = $this->createMock(EmbeddingConfigurationInterface::class);
        $config
            ->expects($this->never())
            ->method('getDefaultEmbeddingModelFieldSuffix');

        $factory = new EmbeddingFieldFactory($config);
        $customType = 'custom_model';

        $field = $factory->create($customType);

        $this->assertSame(
            $customType,
            $field->getType(),
            'Factory should use the explicit type when provided'
        );
    }
}
