<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Bootstrapper;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Ibexa\Bundle\RepositoryInstaller\Bootstrapper\DoctrineMigrationsSchemaHook;
use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TaggedMigrationsRunner is final, so the seam this asserts on is one level down: the runner
 * unconditionally calls MetadataStorage::ensureInitialized() before doing anything else, so whether
 * that happens is exactly "did the hook run the migrations or not".
 *
 * @covers \Ibexa\Bundle\RepositoryInstaller\Bootstrapper\DoctrineMigrationsSchemaHook
 */
final class DoctrineMigrationsSchemaHookTest extends TestCase
{
    public function testInstallSchemaOptionDefaultsToFalse(): void
    {
        $hook = new DoctrineMigrationsSchemaHook(
            $this->createRunner($this->createMock(MetadataStorage::class))
        );

        self::assertFalse($this->resolve($hook, [])[DoctrineMigrationsSchemaHook::OPTION_INSTALL_SCHEMA]);
    }

    public function testRunsTaggedMigrationsWhenEnabled(): void
    {
        $metadataStorage = $this->createMock(MetadataStorage::class);
        $metadataStorage->expects(self::once())->method('ensureInitialized');

        $hook = new DoctrineMigrationsSchemaHook($this->createRunner($metadataStorage));

        $hook($this->resolve($hook, [DoctrineMigrationsSchemaHook::OPTION_INSTALL_SCHEMA => true]));
    }

    public function testDoesNotRunTaggedMigrationsWhenLeftAtItsDefault(): void
    {
        $metadataStorage = $this->createMock(MetadataStorage::class);
        $metadataStorage->expects(self::never())->method('ensureInitialized');

        $hook = new DoctrineMigrationsSchemaHook($this->createRunner($metadataStorage));

        $hook($this->resolve($hook, []));
    }

    /**
     * @param \Doctrine\Migrations\Metadata\Storage\MetadataStorage&\PHPUnit\Framework\MockObject\MockObject $metadataStorage
     */
    private function createRunner(MockObject $metadataStorage): TaggedMigrationsRunner
    {
        // An empty migration list makes run() stop right after ensureInitialized(), so no
        // connection, executor or plan is needed to tell "ran" from "didn't run" apart.
        $planCalculator = $this->createStub(MigrationPlanCalculator::class);
        $planCalculator->method('getMigrations')->willReturn(new AvailableMigrationsList([]));

        $dependencyFactory = $this->createStub(DependencyFactory::class);
        $dependencyFactory->method('getMetadataStorage')->willReturn($metadataStorage);
        $dependencyFactory->method('getMigrationPlanCalculator')->willReturn($planCalculator);

        return new TaggedMigrationsRunner($dependencyFactory);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolve(DoctrineMigrationsSchemaHook $hook, array $options): array
    {
        $resolver = new OptionsResolver();
        $hook->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
