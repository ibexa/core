<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Options;

interface MutableOptionsBag extends OptionsBag
{
    /**
     * Sets the value of the option identified by $key.
     */
    public function set(
        string $key,
        mixed $value
    ): void;

    /**
     * Removes the option identified by $key.
     */
    public function remove(string $key): void;
}
