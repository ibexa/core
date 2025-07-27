<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test\Persistence\Fixture;

use Ibexa\Contracts\Core\Test\Persistence\Fixture;
use LogicException;

/**
 * Abstract data fixture for file-based fixtures. Handles in-memory caching.
 *
 * @internal for internal use by Repository test setup
 *
 * @phpstan-import-type TFixtureData from \Ibexa\Contracts\Core\Test\Persistence\Fixture
 */
abstract class BaseInMemoryCachedFileFixture implements Fixture
{
    /** @var array<string, TFixtureData> */
    private static array $inMemoryCachedLoadedData = [];

    private string $filePath;

    /**
     * Perform an uncached load of data (always done only once).
     *
     * @phpstan-return TFixtureData
     */
    abstract protected function loadFixture(): array;

    final public function getFilePath(): string
    {
        return $this->filePath;
    }

    final public function __construct(string $filePath)
    {
        $this->filePath = $this->getRealPath($filePath);
    }

    final public function load(): array
    {
        // avoid reading disc to load the same file multiple times
        if (!isset(self::$inMemoryCachedLoadedData[$this->filePath])) {
            self::$inMemoryCachedLoadedData[$this->filePath] = $this->loadFixture();
        }

        return self::$inMemoryCachedLoadedData[$this->filePath] ?? [];
    }

    private function getRealPath(string $filePath): string
    {
        $path = realpath($filePath);
        if (false === $path) {
            throw new LogicException("The fixture file does not exist: $filePath");
        }

        return $path;
    }
}
