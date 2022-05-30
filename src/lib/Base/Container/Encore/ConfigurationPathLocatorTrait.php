<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Container\Encore;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait ConfigurationPathLocatorTrait
{
    public function locateConfigurationFiles(
        array $bundlesMetadata,
        array $configFiles,
        string $rootPath
    ): array {
        $paths = [];

        foreach ($configFiles as $configFile => $options) {
            $finder = new Finder();
            $finder
                ->in(array_column($bundlesMetadata, 'path'))
                ->path('Resources/encore')
                ->name($configFile)
                ->files();

            /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
            foreach ($finder as $fileInfo) {
                if ($options['deprecated'] ?? false) {
                    trigger_deprecation(
                        'ibexa/core',
                        '4.0.0',
                        'Support for old configuration files is deprecated, please update name of %s file, to %s',
                        $fileInfo->getPathname(),
                        $options['alternative']
                    );
                }

                $paths[] = preg_replace(
                    '/^' . preg_quote($rootPath, '/') . '/',
                    './',
                    $fileInfo->getRealPath()
                );
            }
        }

        return $paths;
    }

    public function dumpConfigurationPaths(
        string $configName,
        string $targetPath,
        array $paths
    ): void {
        $filesystem = new Filesystem();
        $filesystem->mkdir($targetPath);
        $filesystem->dumpFile(
            $targetPath . '/' . $configName,
            sprintf('module.exports = %s;', json_encode($paths))
        );
    }
}
