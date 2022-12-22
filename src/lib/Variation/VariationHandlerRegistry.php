<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Variation;

use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Traversable;

final class VariationHandlerRegistry
{
    /** @var iterable<string, \Ibexa\Contracts\Core\Variation\VariationHandler> */
    private iterable $variationHandlers;

    public function __construct(iterable $variationHandlers)
    {
        $handlers = $variationHandlers instanceof Traversable
            ? iterator_to_array($variationHandlers)
            : $variationHandlers;

        foreach ($handlers as $identifier => $handler) {
            $this->setVariationHandler($identifier, $handler);
        }
    }

    public function hasVariationHandler(string $identifier): bool
    {
        return isset($this->variationHandlers[$identifier]);
    }

    public function getVariationHandler(string $identifier): VariationHandler
    {
        if (!$this->hasVariationHandler($identifier)) {
            throw new InvalidArgumentException('identifier', 'VariationHandler is not registered');
        }

        return $this->variationHandlers[$identifier];
    }

    public function setVariationHandler(string $identifier, VariationHandler $variationHandler): void
    {
        $this->variationHandlers[$identifier] = $variationHandler;
    }
}
