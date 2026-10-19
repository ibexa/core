<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Stream;

use Ibexa\Core\IO\Exception\IOException;
use Ibexa\Core\IO\Stream\StreamSeeker;
use PHPUnit\Framework\TestCase;

final class StreamSeekerTest extends TestCase
{
    private const DATA = '0123456789';

    /**
     * @dataProvider provideForSeek
     *
     * @param resource $stream
     */
    public function testSeek(
        $stream,
        int $offset,
        string $expectedRemainder
    ): void {
        StreamSeeker::seek($stream, $offset);

        self::assertSame($expectedRemainder, stream_get_contents($stream));
    }

    /**
     * @return iterable<string, array{resource, int, string}>
     */
    public function provideForSeek(): iterable
    {
        yield 'seekable, zero offset' => [self::createSeekableStream(), 0, '0123456789'];
        yield 'seekable, middle offset' => [self::createSeekableStream(), 4, '456789'];
        yield 'non-seekable, zero offset' => [NonSeekableStreamWrapper::open(self::DATA), 0, '0123456789'];
        yield 'non-seekable, middle offset' => [NonSeekableStreamWrapper::open(self::DATA), 4, '456789'];
        yield 'non-seekable, offset equal to size' => [NonSeekableStreamWrapper::open(self::DATA), 10, ''];
        yield 'non-seekable, offset above skip chunk size' => [
            NonSeekableStreamWrapper::open(str_repeat('a', 3 * 1048576) . 'tail'),
            3 * 1048576,
            'tail',
        ];
    }

    /**
     * @dataProvider provideForSeekBeyondEndOfStream
     *
     * @param resource $stream
     */
    public function testSeekBeyondEndOfStreamThrowsAndClosesStream($stream): void
    {
        try {
            StreamSeeker::seek($stream, 11);
            self::fail('Expected IOException');
        } catch (IOException $e) {
            self::assertSame('Unable to move stream to offset 11, stream ended after 10 bytes', $e->getMessage());
        }

        self::assertFalse(is_resource($stream));
    }

    /**
     * @return iterable<string, array{resource}>
     */
    public function provideForSeekBeyondEndOfStream(): iterable
    {
        yield 'seekable' => [self::createSeekableStream()];
        yield 'non-seekable' => [NonSeekableStreamWrapper::open(self::DATA)];
    }

    /**
     * @return resource
     */
    private static function createSeekableStream()
    {
        $stream = fopen('php://memory', 'w+b');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open memory stream');
        }

        fwrite($stream, self::DATA);
        rewind($stream);

        return $stream;
    }
}
