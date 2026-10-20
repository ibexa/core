<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\View\Builder;

/**
 * A simple registry of ViewBuilders that uses the ViewBuilder's match() method to identify the builder.
 */
interface ViewBuilderRegistry
{
    /**
     * Returns the ViewBuilder matching the argument.
     *
     * @param mixed $argument
     *
     * @return ViewBuilder|null The ViewBuilder, or null if there's none.
     */
    public function getFromRegistry($argument);

    /**
     * Adds ViewBuilders from the $objects array to the registry.
     *
     * @param ViewBuilder[] $objects
     */
    public function addToRegistry(array $objects);
}
