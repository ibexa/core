<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Container\Encore;

use Ibexa\Contracts\Core\Container\Encore\ConfigurationDumper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Ibexa\Contracts\Core\Container\Encore\ConfigurationDumper
 */
final class ConfigurationDumperTest extends TestCase
{
    private const string PROJECT_DIR = '/var/io-tests/';
    private const string FOO_BAR_BUNDLE_DIR = 'foo-bar';

    private Filesystem $filesystem;

    private string $projectDir;

    private string $fooBarBundlePath;

    protected function setUp(): void
    {
        $this->projectDir = dirname(__DIR__, 4) . self::PROJECT_DIR;
        $this->fooBarBundlePath = $this->projectDir . self::FOO_BAR_BUNDLE_DIR;
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->fooBarBundlePath);
        $this->filesystem->dumpFile(
            $this->fooBarBundlePath . '/Resources/encore/foo-bar.js',
            'console.log("Hello, Foo Bar!");'
        );
        $this->filesystem->dumpFile(
            $this->projectDir . 'encore/foo-bar.js',
            'console.log("Hello, world!");'
        );
    }

    /**
     * @throws \JsonException
     */
    public function testDumpCustomConfiguration(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('getParameter')->willReturnMap(
            [
                [
                    'kernel.bundles_metadata',
                    ['FooBar' => ['path' => $this->fooBarBundlePath]],
                ],
                ['kernel.project_dir', $this->projectDir],
            ],
        );
        $configurationDumper = new ConfigurationDumper($containerMock);
        $configurationDumper->dumpCustomConfiguration(['foo-bar.js' => ['foo-bar.js' => []]]);

        $compiledFilePath = $this->projectDir . '/var/encore/foo-bar.js';
        self::assertFileExists($compiledFilePath);
        $compiledFileContents = file_get_contents($compiledFilePath);
        self::assertNotFalse($compiledFileContents, "Failed to read compiled file '$compiledFilePath' contents");
        self::assertMatchesRegularExpression(
            '@^module\.exports = \[.*io-tests\\\/foo-bar\\\/Resources\\\/encore\\\/foo-bar\.js@',
            $compiledFileContents
        );
        self::assertMatchesRegularExpression(
            '@^module\.exports = \[.*io-tests\\\/encore\\\/foo-bar\.js@',
            $compiledFileContents
        );
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDir);
    }
}
