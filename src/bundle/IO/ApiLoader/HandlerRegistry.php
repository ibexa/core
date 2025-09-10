<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\ApiLoader;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Registry of IO handlers, given an alias.
 *
 * @template THandlerType of object
 */
class HandlerRegistry
{
    /**
     * Map of a handler id to a handler service instance.
     *
     * @phpstan-var array<string, THandlerType>
     */
    private array $handlersMap = [];

    /**
     * @phpstan-param array<string, THandlerType> $handlersMap
     */
    public function setHandlersMap(array $handlersMap): void
    {
        $this->handlersMap = $handlersMap;
    }

    /**
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException If the requested handler doesn't exist
     *
     * @phpstan-return THandlerType
     */
    public function getConfiguredHandler(string $handlerName): object
    {
        if (!isset($this->handlersMap[$handlerName])) {
            throw new InvalidConfigurationException("Unknown handler $handlerName");
        }

        return $this->handlersMap[$handlerName];
    }
}
