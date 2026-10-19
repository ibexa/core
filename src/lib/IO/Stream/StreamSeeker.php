<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Stream;

use Ibexa\Core\IO\Exception\IOException;

/**
 * @internal
 */
final class StreamSeeker
{
    private const SKIP_CHUNK_SIZE = 1048576;

    /**
     * Non-seekable streams are read and discarded up to $offset, which is costly for remote storage.
     *
     * @param resource $stream closed when the offset cannot be reached
     *
     * @throws IOException if the stream ends before reaching $offset
     */
    public static function seek(
        $stream,
        int $offset
    ): void {
        if ($offset <= 0) {
            return;
        }

        if (stream_get_meta_data($stream)['seekable'] && fseek($stream, $offset) === 0) {
            $size = fstat($stream)['size'] ?? null;
            if ($size !== null && $offset > $size) {
                self::fail($stream, $offset, $size);
            }

            return;
        }

        $remaining = $offset;
        while ($remaining > 0) {
            $chunk = fread($stream, min(self::SKIP_CHUNK_SIZE, $remaining));
            if ($chunk === false || $chunk === '') {
                self::fail($stream, $offset, $offset - $remaining);
            }

            $remaining -= strlen($chunk);
        }
    }

    /**
     * @param resource $stream
     *
     * @return never
     */
    private static function fail(
        $stream,
        int $offset,
        int $streamSize
    ): void {
        fclose($stream);

        throw new IOException(sprintf(
            'Unable to move stream to offset %d, stream ended after %d bytes',
            $offset,
            $streamSize
        ));
    }
}
