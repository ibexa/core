<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Filter;

/**
 * Base implementation of FilterInterface, handling options.
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function __construct(private array $options = [])
    {
    }

    public function setOption(string $optionName, mixed $value): void
    {
        $this->options[$optionName] = $value;
    }

    public function getOption(string $optionName, mixed $defaultValue = null): mixed
    {
        return $this->options[$optionName] ?? $defaultValue;
    }

    public function hasOption(string $optionName): bool
    {
        return isset($this->options[$optionName]);
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
