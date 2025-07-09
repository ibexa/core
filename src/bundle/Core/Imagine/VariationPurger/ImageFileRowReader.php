<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

/**
 * Reads original image files from a data source.
 */
interface ImageFileRowReader
{
    /**
     * Initializes the reader.
     *
     * Can for instance be used to create and execute a database query.
     */
    public function init(): void;

    /**
     * Returns the next row from the data source.
     */
    public function getRow(): ?string;

    /**
     * Returns the total row count.
     *
     * @phpstan-return int<0, max>
     */
    public function getCount(): int;
}
