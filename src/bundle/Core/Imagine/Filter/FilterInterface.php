<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Filter;

use Imagine\Filter\FilterInterface as BaseFilterInterface;

interface FilterInterface extends BaseFilterInterface
{
    /**
     * Sets $value for $optionName.
     */
    public function setOption(string $optionName, mixed $value): void;

    /**
     * Returns value for $optionName.
     * Defaults to $defaultValue if $optionName doesn't exist.
     */
    public function getOption(string $optionName, mixed $defaultValue = null): mixed;

    /**
     * Checks if $optionName exists and has a value.
     */
    public function hasOption(string $optionName): bool;

    /**
     * Replaces inner options by $options.
     *
     * @phpstan-param array<string, mixed> $options
     */
    public function setOptions(array $options): void;

    /**
     * Returns all options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;
}
