<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\Suggestion\Collector;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Suggestion\ConfigSuggestion;

/**
 * Interface for configuration suggestion collectors.
 */
interface SuggestionCollectorInterface
{
    /**
     * Adds a config suggestion to the list.
     *
     * @param ConfigSuggestion $suggestion
     */
    public function addSuggestion(ConfigSuggestion $suggestion);

    /**
     * Returns all config suggestions.
     *
     * @return ConfigSuggestion[]
     */
    public function getSuggestions();

    /**
     * @return bool
     */
    public function hasSuggestions();
}
