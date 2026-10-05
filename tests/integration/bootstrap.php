<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

use Ibexa\Bundle\RepositoryInstaller\Bootstrapper\DoctrineMigrationsSchemaHook;
use Ibexa\Contracts\Test\Core\Bootstrapper\Bootstrapper;
use Ibexa\Contracts\Test\Core\Bootstrapper\DatabaseSchemaHook;

require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Selects which of the two coexisting schema install paths this run exercises: the
// SchemaBuilderEvent one by default, or the Doctrine Migrations one with
// IBEXA_TEST_SCHEMA_BUILDER_EVENT_ENABLED=0.
$schemaBuilderEventEnabled = getenv('IBEXA_TEST_SCHEMA_BUILDER_EVENT_ENABLED') !== '0';

(new Bootstrapper())->bootstrap(null, [
    DatabaseSchemaHook::class => [DatabaseSchemaHook::OPTION_LOAD_SCHEMA => $schemaBuilderEventEnabled],
    DoctrineMigrationsSchemaHook::class => [DoctrineMigrationsSchemaHook::OPTION_INSTALL_SCHEMA => !$schemaBuilderEventEnabled],
    // BaseFixtureHook stays on for both paths, like the test kernel's own fixtures: the tests here
    // assert against test_data.yaml's content, not the clean-install content ImportDataMigration
    // inserts. The fixture importer truncates the tables it fills, so the fixtures replace that
    // content rather than colliding with it.
]);
