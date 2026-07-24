<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Provider\SchemaProvider;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;

/**
 * Bridges Doctrine Migrations' {@see SchemaProvider} to Ibexa's own legacy, event-driven schema
 * builder ({@see SchemaBuilderInterface}, backed by {@see \Ibexa\Contracts\DoctrineSchema\Event\SchemaBuilderEvent}
 * and every installed package's {@see \Ibexa\Bundle\RepositoryInstaller\Event\Subscriber\BuildSchemaSubscriber}).
 *
 * Wired onto {@see \Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaOnlyDependencyFactory} so that
 * "ibexa:doctrine:migrations:diff" (and any other command that needs a target schema) can compare the live
 * database against the schema Ibexa's packages expect, without requiring a Doctrine ORM entity manager.
 */
final class SchemaBuilderEventSchemaProvider implements SchemaProvider
{
    private SchemaBuilderInterface $schemaBuilder;

    public function __construct(SchemaBuilderInterface $schemaBuilder)
    {
        $this->schemaBuilder = $schemaBuilder;
    }

    public function createSchema(): Schema
    {
        return $this->schemaBuilder->buildSchema();
    }
}
