<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Bootstrapper;

use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use Ibexa\Contracts\Test\Core\Bootstrapper\DatabaseSchemaHook;
use Ibexa\Contracts\Test\Core\Bootstrapper\FixtureHook;
use Ibexa\Contracts\Test\Core\Bootstrapper\HookInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @experimental
 *
 * Installs the database schema the way a real `ibexa:install` does when the SchemaBuilderEvent path
 * is turned off: by running every Ibexa-tagged Doctrine migration via {@see TaggedMigrationsRunner}
 * — the very same service {@see \Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller} uses, so
 * a test database built this way is built by the production code path, not by a re-implementation
 * of it.
 *
 * This is the counterpart of {@see DatabaseSchemaHook}, and the two are mutually exclusive: each
 * creates the same tables, so enabling both makes the second one fail on tables that already exist.
 * Running an integration suite once with each is what proves the two install paths converge.
 *
 * Unlike every other built-in hook, this one is **disabled by default**. Enabling it by default
 * would add a second schema install on top of {@see DatabaseSchemaHook}'s in every existing suite,
 * breaking all of them the moment this package is upgraded. Opt in per bootstrap run, turning the
 * legacy path off in the same breath:
 *
 * ```php
 * (new Bootstrapper())->bootstrap(null, [
 *     DatabaseSchemaHook::class => [DatabaseSchemaHook::OPTION_LOAD_SCHEMA => false],
 *     DoctrineMigrationsSchemaHook::class => [DoctrineMigrationsSchemaHook::OPTION_INSTALL_SCHEMA => true],
 * ]);
 * ```
 *
 * Registered only in the "test" environment, and removed from the container along with
 * {@see TaggedMigrationsRunner} itself when "ibexa/doctrine-migrations" isn't installed/enabled, by
 * {@see \Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\RemoveTaggedMigrationsRunnerPass}.
 */
final class DoctrineMigrationsSchemaHook implements HookInterface
{
    /**
     * Fixed tag priority this hook is registered at — after ibexa/test-core's
     * {@see DatabaseSchemaHook} (1000), before its {@see FixtureHook} (900).
     *
     * Ordering against DatabaseSchemaHook only matters when both are enabled, which is a
     * misconfiguration; running second makes that misconfiguration fail at a predictable point
     * ("table already exists" out of the migrations) rather than in whichever order the tag
     * collection happened to produce.
     */
    public const PRIORITY = 990;

    public const OPTION_INSTALL_SCHEMA = 'install_schema';

    private TaggedMigrationsRunner $taggedMigrationsRunner;

    public function __construct(TaggedMigrationsRunner $taggedMigrationsRunner)
    {
        $this->taggedMigrationsRunner = $taggedMigrationsRunner;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define(self::OPTION_INSTALL_SCHEMA)
            ->default(false)
            ->allowedTypes('bool');
    }

    public function __invoke(array $options): void
    {
        if (!$options[self::OPTION_INSTALL_SCHEMA]) {
            return;
        }

        // Executions stay recorded in the Doctrine Migrations versioning table on purpose - that
        // is what a real install leaves behind, and it is what makes a second run (a test calling
        // the installer itself, say) correctly skip instead of re-applying. Contrast with
        // ibexa/migrations' MigrationHook, which resets its own bookkeeping because its migrations
        // are test fixtures rather than part of the installed state.
        $this->taggedMigrationsRunner->run();
    }
}
