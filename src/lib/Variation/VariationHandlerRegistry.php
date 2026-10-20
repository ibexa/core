<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Variation;

use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\Variation\VariationHandler;

final class VariationHandlerRegistry
{
    /** @var array<string, VariationHandler> */
    private array $variationHandlers;

    /**
     * @param iterable<string, VariationHandler> $variationHandlers
     */
    public function __construct(iterable $variationHandlers)
    {
        foreach ($variationHandlers as $identifier => $handler) {
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

    public function setVariationHandler(
        string $identifier,
        VariationHandler $variationHandler
    ): void {
        $this->variationHandlers[$identifier] = $variationHandler;
    }
}
