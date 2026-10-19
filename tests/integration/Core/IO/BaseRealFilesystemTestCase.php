<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\IO;

use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use League\Flysystem\FilesystemOperator;

/**
 * Base for tests which need Flysystem IO to run on the real local file system.
 */
abstract class BaseRealFilesystemTestCase extends IbexaKernelTestCase
{
    protected FilesystemOperator $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();
        $adapter = $container->get(FlysystemTestAdapterInterface::class);
        self::assertInstanceOf(FlysystemTestAdapterInterface::class, $adapter);
        $adapter->useRealFileSystem(true);

        $filesystem = $container->get('ibexa.core.io.flysystem.default_filesystem');
        self::assertInstanceOf(FilesystemOperator::class, $filesystem);
        $this->filesystem = $filesystem;
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory('/');

        parent::tearDown();
    }
}
