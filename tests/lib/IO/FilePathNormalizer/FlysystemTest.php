<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\FilePathNormalizer;

use Ibexa\Core\IO\FilePathNormalizer\Flysystem;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter;
use League\Flysystem\WhitespacePathNormalizer;
use PHPUnit\Framework\TestCase;

final class FlysystemTest extends TestCase
{
    /** @var \Ibexa\Core\IO\FilePathNormalizer\Flysystem */
    private $filePathNormalizer;

    /** @var \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $slugConverter;

    public function setUp(): void
    {
        $this->slugConverter = $this->createMock(SlugConverter::class);
        $this->filePathNormalizer = new Flysystem($this->slugConverter, new WhitespacePathNormalizer());
    }

    /**
     * @dataProvider providerForTestNormalizePath
     */
    public function testNormalizePath(
        string $originalPath,
        string $fileName,
        string $sluggedFileName,
        string $regex,
        bool $doHash
    ): void {
        $this->slugConverter
            ->expects(self::once())
            ->method('convert')
            ->with($fileName)
            ->willReturn($sluggedFileName);

        $normalizedPath = $this->filePathNormalizer->normalizePath($originalPath, $doHash);

        self::assertStringEndsWith($sluggedFileName, $normalizedPath);
        self::assertRegExp($regex, $normalizedPath);
    }

    public function providerForTestNormalizePath(): array
    {
        $defaultPattern = '/\/[0-9a-f]{12}-';

        return [
            'No special chars' => [
                '4/3/2/234/1/image.jpg',
                'image.jpg',
                'image.jpg',
                $defaultPattern . 'image.jpg/',
                true,
            ],
            'Spaces in the filename' => [
                '4/3/2/234/1/image with spaces.jpg',
                'image with spaces.jpg',
                'image-with-spaces.jpg',
                $defaultPattern . 'image-with-spaces.jpg/',
                true,
            ],
            'Encoded spaces in the name' => [
                '4/3/2/234/1/image%20+no+spaces.jpg',
                'image%20+no+spaces.jpg',
                'image-20-nospaces.jpg',
                $defaultPattern . 'image-20-nospaces.jpg/',
                true,
            ],
            'Special chars in the name' => [
                '4/3/2/234/1/image%20+no+spaces?.jpg',
                'image%20+no+spaces?.jpg',
                'image-20-nospaces.jpg',
                $defaultPattern . 'image-20-nospaces.jpg/',
                true,
            ],
            'Already hashed name' => [
                '4/3/2/234/1/14ff44718877-hashed.jpg',
                '14ff44718877-hashed.jpg',
                '14ff44718877-hashed.jpg',
                '/^4\/3\/2\/234\/1\/14ff44718877-hashed.jpg$/',
                true,
            ],
            'No special chars and no hashing' => [
                '4/3/2/234/1/image.jpg',
                'image.jpg',
                'image.jpg',
                '/\/image.jpg/',
                false,
            ],
            'Special chars and no hashing' => [
                '4/3/2/234/1/image%20+no+spaces.jpg',
                'image%20+no+spaces.jpg',
                'image-20-nospaces.jpg',
                '/\/image-20-nospaces.jpg/',
                false,
            ],
            'Already hashed name and no hashing' => [
                '4/3/2/234/1/14ff44718877-hashed.jpg',
                '14ff44718877-hashed.jpg',
                '14ff44718877-hashed.jpg',
                '/^4\/3\/2\/234\/1\/14ff44718877-hashed.jpg$/',
                false,
            ],
        ];
    }

    public function testNormalizePathWithRealFilePath(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_');
        file_put_contents($tempFile, 'test image content');

        try {
            $md5File = md5_file($tempFile);
            self::assertNotFalse($md5File);
            $expectedHash = substr($md5File, 0, 12);

            $this->slugConverter
                ->expects(self::once())
                ->method('convert')
                ->with('image.jpg')
                ->willReturn('image.jpg');

            $normalizedPath = $this->filePathNormalizer->normalizePath(
                '4/3/2/234/1/image.jpg',
                true,
                $tempFile,
            );

            self::assertSame('4/3/2/234/1/' . $expectedHash . '-image.jpg', $normalizedPath);
        } finally {
            unlink($tempFile);
        }
    }

    public function testNormalizePathWithRealFilePathIsDeterministic(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_');
        file_put_contents($tempFile, 'test image content');

        try {
            $this->slugConverter
                ->expects(self::exactly(2))
                ->method('convert')
                ->with('image.jpg')
                ->willReturn('image.jpg');

            $first = $this->filePathNormalizer->normalizePath('4/3/2/234/1/image.jpg', true, $tempFile);
            $second = $this->filePathNormalizer->normalizePath('4/3/2/234/1/image.jpg', true, $tempFile);

            self::assertSame($first, $second);
        } finally {
            unlink($tempFile);
        }
    }
}
