<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Options;

interface OptionsBag
{
    public function all(): array;

    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;
}
