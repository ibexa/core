<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Variation;

/**
 * Handles physical purging of image variations from storage.
 */
interface VariationPurger
{
    /**
     * Purge all variations generated for aliases in $aliasNames.
     *
     * @param string[] $aliasNames
     */
    public function purge(array $aliasNames): void;
}
