<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\Schema\Schema;
use Ibexa\Bundle\RepositoryInstaller\Migration\SchemaBuilderEventSchemaProvider;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\RepositoryInstaller\Migration\SchemaBuilderEventSchemaProvider
 */
final class SchemaBuilderEventSchemaProviderTest extends TestCase
{
    public function testCreateSchemaDelegatesToSchemaBuilder(): void
    {
        $schema = new Schema();

        $schemaBuilder = $this->createMock(SchemaBuilderInterface::class);
        $schemaBuilder->expects(self::once())
            ->method('buildSchema')
            ->willReturn($schema);

        $provider = new SchemaBuilderEventSchemaProvider($schemaBuilder);

        self::assertSame($schema, $provider->createSchema());
    }
}
