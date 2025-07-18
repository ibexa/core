<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\Migration\FileLister;

/**
 * Reads files from a data source.
 */
interface FileRowReaderInterface
{
    /**
     * Initializes the reader.
     *
     * Can for instance be used to create and execute a database query.
     */
    public function init(): void;

    /**
     * Returns the next row from the data source.
     *
     * @return string|null The row's value, or null if none.
     */
    public function getRow(): ?string;

    /**
     * Returns the total row count.
     *
     * @phpstan-return int<0, max>
     */
    public function getCount(): int;
}
