<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\MimeTypeDetector;

use Ibexa\Core\IO\MimeTypeDetector\FileInfo as MimeTypeDetector;
use PHPUnit\Framework\TestCase;

class FileInfoTest extends TestCase
{
    protected MimeTypeDetector $mimeTypeDetector;

    protected function setUp(): void
    {
        $this->mimeTypeDetector = new MimeTypeDetector();
    }

    protected function getFixture(): string
    {
        return __DIR__ . '/../../_fixtures/squirrel-developers.jpg';
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGetFromPath(): void
    {
        self::assertEquals(
            'image/jpeg',
            $this->mimeTypeDetector->getFromPath(
                $this->getFixture()
            )
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGetFromBuffer(): void
    {
        $buffer = file_get_contents($this->getFixture());
        self::assertNotFalse($buffer, 'Failed to read fixture');
        self::assertEquals(
            'image/jpeg',
            $this->mimeTypeDetector->getFromBuffer(
                $buffer
            )
        );
    }
}
