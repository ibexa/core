<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Options;

interface OptionsBag
{
    /**
     * Returns all options as an associative array.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Returns the value of the option identified by $key.
     *
     * If the option does not exist, returns $default.
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed;

    /**
     * Checks if the option identified by $key exists.
     */
    public function has(string $key): bool;
}
