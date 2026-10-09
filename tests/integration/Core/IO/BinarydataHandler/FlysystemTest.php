<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\IO\BinarydataHandler;

use Ibexa\Contracts\Core\IO\BinaryFileCreateStruct;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Tests\Integration\Core\IO\BaseRealFilesystemTestCase;
use League\Flysystem\Visibility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @covers \Ibexa\Core\IO\IOBinarydataHandler\Flysystem
 */
final class FlysystemTest extends BaseRealFilesystemTestCase
{
    private IOBinarydataHandler $binaryDataHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->binaryDataHandler = $this->getBinaryDataHandler(self::getContainer());
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

    private function getBinaryDataHandler(ContainerInterface $container): IOBinarydataHandler
    {
        return $container->get('ibexa.core.io.binarydata_handler.flysystem');
    }
}
