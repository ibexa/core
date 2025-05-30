<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Options;

interface MutableOptionsBag extends OptionsBag
{
    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;
}
