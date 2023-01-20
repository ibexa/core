<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\IO\BinarydataHandler;

use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Tests\Integration\Core\IO\FlysystemTestAdapterInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @covers \Ibexa\Core\IO\IOBinarydataHandler\Flysystem
 */
final class FlysystemTest extends IbexaKernelTestCase
{
    private IOBinarydataHandler $binaryDataHandler;

    private FilesystemOperator $filesystem;

    private static function getAdapter(): FlysystemTestAdapterInterface
    {
        $adapter = self::getContainer()->get(FlysystemTestAdapterInterface::class);
        self::assertInstanceOf(FlysystemTestAdapterInterface::class, $adapter);

        return $adapter;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();
        // by default, we use InMemory "virtual" adapter for tests,
        // but here we need to test real file system permissions
        self::getAdapter()->useRealFileSystem(true);

        $this->binaryDataHandler = $this->getBinaryDataHandler($container);
        $this->filesystem = $this->getFlysystemFilesystem($container);
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory('/');
    }

    public function testCreateSetsCorrectPermissions(): void
    {
        $handle = fopen(dirname(__DIR__, 2) . '/Repository/FieldType/_fixtures/image.png', 'rob');
        try {
            $binaryFileCreateStruct = new BinaryFileCreateStruct();
            $binaryFileCreateStruct->id = 'foo/image.png';
            $binaryFileCreateStruct->mimeType = 'image/png';
            $binaryFileCreateStruct->setInputStream($handle);
            $this->binaryDataHandler->create($binaryFileCreateStruct);
            foreach ($this->filesystem->listContents('/') as $storageAttributes) {
                self::assertSame(
                    Visibility::PUBLIC,
                    $storageAttributes->visibility(),
                    sprintf(
                        'Visibility of "%s" %s is expected to be %s',
                        $storageAttributes->path(),
                        $storageAttributes->type(),
                        Visibility::PUBLIC
                    )
                );
            }
        } finally {
            fclose($handle);
        }
    }

    private function getFlysystemFilesystem(ContainerInterface $container): FilesystemOperator
    {
        return $container->get('ibexa.core.io.flysystem.default_filesystem');
    }

    private function getBinaryDataHandler(ContainerInterface $container): IOBinarydataHandler
    {
        return $container->get('ibexa.core.io.binarydata_handler.flysystem');
    }
}
