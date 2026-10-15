<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Container\Encore;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Scans project and bundles resources for the given configuration paths.
 * To be used only during container building, in Bundle Extension class.
 *
 * @internal for internal use by Ibexa 1st party packages to provide specific extension points
 */
final class ConfigurationDumper
{
    public const string ENCORE_DIR = 'encore';
    public const string ENCORE_TARGET_PATH = 'var/encore';

    private ContainerInterface $containerBuilder;

    public function __construct(ContainerInterface $containerBuilder)
    {
        $this->containerBuilder = $containerBuilder;
    }

    /**
     * @param array<string, array<string, array{'deprecated'?: bool, 'alternative'?: string}>> $webpackConfigNames
     *
     * @throws \JsonException
     */
    public function dumpCustomConfiguration(
        array $webpackConfigNames
    ): void {
        /** @var array<string, array{path: string, namespace: string}> $bundlesMetadata */
        $bundlesMetadata = $this->containerBuilder->getParameter('kernel.bundles_metadata');
        $rootPath = $this->getProjectDirectory() . '/';
        foreach ($webpackConfigNames as $configName => $configFiles) {
            $paths = $this->locateConfigurationFiles($bundlesMetadata, $configFiles, $rootPath);
            $this->dumpConfigurationPaths(
                $configName,
                $rootPath . self::ENCORE_TARGET_PATH,
                $paths
            );
        }
    }

    /**
     * @param array<string, array{path: string, namespace: string}> $bundlesMetadata
     * @param array<string, array{'deprecated'?: bool, 'alternative'?: string}> $configFiles
     *
     * @return array<string>
     */
    private function locateConfigurationFiles(
        array $bundlesMetadata,
        array $configFiles,
        string $rootPath
    ): array {
        $paths = [];
        foreach ($configFiles as $configFile => $options) {
            $finder = $this->createFinder($bundlesMetadata, $configFile, $rootPath);

            /** @var SplFileInfo $fileInfo */
            foreach ($finder as $fileInfo) {
                if ($options['deprecated'] ?? false) {
                    trigger_deprecation(
                        'ibexa/core',
                        '4.0.0',
                        'Support for old configuration files is deprecated, please update name of %s file, to %s',
                        $fileInfo->getPathname(),
                        $options['alternative'] ?? ''
                    );
                }

                $path = $fileInfo->getRealPath();
                if (str_starts_with($path, $rootPath)) {
                    $path = './' . substr($path, strlen($rootPath));
                }

                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @param array<string> $paths
     *
     * @throws \JsonException
     */
    private function dumpConfigurationPaths(
        string $configName,
        string $targetPath,
        array $paths
    ): void {
        $filesystem = new Filesystem();
        $filesystem->dumpFile(
            $targetPath . '/' . $configName,
            sprintf('module.exports = %s;', json_encode($paths, JSON_THROW_ON_ERROR))
        );
    }

    /**
     * @param array<string, array{path: string, namespace: string}> $bundlesMetadata
     */
    private function createFinder(
        array $bundlesMetadata,
        string $configFile,
        string $rootPath
    ): Finder {
        $finder = new Finder();
        $finder
            ->in(array_column($bundlesMetadata, 'path'))
            ->path('Resources/' . self::ENCORE_DIR)
            ->name($configFile)
            // include top-level project resources
            ->append(
                (new Finder())
                    ->in($rootPath)
                    ->path(self::ENCORE_DIR)
                    ->name($configFile)
                    ->depth(1)
                    ->files()
            )
            ->files();

        return $finder;
    }

    private function getProjectDirectory(): string
    {
        $projectDirectory = $this->containerBuilder->getParameter('kernel.project_dir');
        if (!is_string($projectDirectory)) {
            throw new \LogicException('Kernel project directory parameter is either not set or is not a string.');
        }

        return $projectDirectory;
    }
}
