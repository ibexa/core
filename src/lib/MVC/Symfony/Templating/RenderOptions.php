<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating;

use Ibexa\Contracts\Core\Options\MutableOptionsBag;

final class RenderOptions implements MutableOptionsBag
{
    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function all(): array
    {
        return $this->options;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->options[$key];
        }

        return $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->options[$key] = $value;
    }

    public function has(string $key): bool
    {
        return !empty($this->options[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->options[$key]);
    }
}
