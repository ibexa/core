<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine;

use InvalidArgumentException;

class PlaceholderProviderRegistry
{
    /**
     * @param array<string, \Ibexa\Bundle\Core\Imagine\PlaceholderProvider> $providers
     */
    public function __construct(private array $providers = [])
    {
    }

    public function addProvider(string $type, PlaceholderProvider $provider): void
    {
        $this->providers[$type] = $provider;
    }

    public function supports(string $type): bool
    {
        return isset($this->providers[$type]);
    }

    public function getProvider(string $type): PlaceholderProvider
    {
        if (!$this->supports($type)) {
            throw new InvalidArgumentException("Unknown placeholder provider: $type");
        }

        return $this->providers[$type];
    }
}
