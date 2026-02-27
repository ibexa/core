<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Common;

use Ibexa\Contracts\Core\Search\FieldType;
use Ibexa\Core\Search\Common\FieldNameGenerator;
use PHPUnit\Framework\TestCase;

final class FieldNameGeneratorTest extends TestCase
{
    public function testGetTypedNameUsesConfiguredMapping(): void
    {
        $generator = new FieldNameGenerator([
            'ibexa_string' => 's',
        ]);

        $fieldType = $this->createMock(FieldType::class);
        $fieldType
            ->method('getType')
            ->willReturn('ibexa_string');

        self::assertSame('title_s', $generator->getTypedName('title', $fieldType));
    }

    public function testGetTypedNameNormalizesEmbeddingFieldTypeWithoutExplicitMapping(): void
    {
        $generator = new FieldNameGenerator([], ['ibexa_dense_vector_']);

        $fieldType = $this->createMock(FieldType::class);
        $fieldType
            ->method('getType')
            ->willReturn('ibexa_dense_vector_gemini_embedding_001_1536_dv');

        self::assertSame(
            'taxonomy_embeddings_gemini_embedding_001_1536_dv',
            $generator->getTypedName('taxonomy_embeddings', $fieldType)
        );
    }

    public function testGetTypedNameReturnsOriginalTypeWhenNoFallbackPrefixMatches(): void
    {
        $generator = new FieldNameGenerator([], ['ibexa_dense_vector_']);

        $fieldType = $this->createMock(FieldType::class);
        $fieldType
            ->method('getType')
            ->willReturn('custom_type');

        self::assertSame('foo_custom_type', $generator->getTypedName('foo', $fieldType));
    }
}
