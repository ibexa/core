<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\IO\Flysystem;

use Ibexa\Tests\Integration\Core\IO\BaseRealFilesystemTestCase;

final class MimeTypeDetectionTest extends BaseRealFilesystemTestCase
{
    /**
     * @dataProvider provideFilesWithInconclusiveContents
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function testDetectsMimeTypeFromContents(string $path, string $contents, string $expectedMimeType): void
    {
        $this->filesystem->write($path, $contents);

        self::assertSame($expectedMimeType, $this->filesystem->mimeType($path));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideFilesWithInconclusiveContents(): iterable
    {
        yield 'plain text without extension' => ['README', "Plain text without extension\n", 'text/plain'];
        yield 'empty file' => ['empty', '', 'application/x-empty'];
        yield 'binary contents with unknown extension' => [
            'blob.unknownext',
            "\x00\x01\x02\xff\xfe binary",
            'application/octet-stream',
        ];
        yield 'plain text with extension of another type' => ['notes.xyz', "Plain text\n", 'text/plain'];
    }
}
