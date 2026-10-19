<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\IO\Stream;

/**
 * Emulates a remote (e.g. AWS S3) stream: readable sequentially, fseek() always fails.
 */
final class NonSeekableStreamWrapper
{
    private const PROTOCOL = 'ibexa-test-non-seekable';

    /** @var resource|null */
    public $context;

    /** @var array<string, string> */
    private static array $contents = [];

    private string $data = '';

    private int $position = 0;

    /**
     * @return resource
     */
    public static function open(string $data)
    {
        if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::PROTOCOL, self::class);
        }

        $key = uniqid('', true);
        self::$contents[$key] = $data;

        $stream = fopen(self::PROTOCOL . '://' . $key, 'rb');
        if ($stream === false) {
            throw new \UnexpectedValueException('Unable to open non-seekable test stream');
        }

        return $stream;
    }

    public function stream_open(string $path): bool
    {
        $key = substr($path, strlen(self::PROTOCOL) + 3);
        if (!isset(self::$contents[$key])) {
            return false;
        }

        $this->data = self::$contents[$key];

        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk = (string)substr($this->data, $this->position, $count);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->data);
    }

    public function stream_seek(): bool
    {
        return false;
    }

    public function stream_tell(): int
    {
        return $this->position;
    }

    /**
     * @return array<mixed>
     */
    public function stream_stat(): array
    {
        return [];
    }
}
