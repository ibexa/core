<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Suggestion\Collector;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Suggestion\Collector\SuggestionCollector;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\Suggestion\ConfigSuggestion;
use PHPUnit\Framework\TestCase;

class SuggestionCollectorTest extends TestCase
{
    public function testAddHasGetSuggestions()
    {
        $collector = new SuggestionCollector();
        $suggestions = [new ConfigSuggestion(), new ConfigSuggestion(), new ConfigSuggestion()];
        foreach ($suggestions as $suggestion) {
            $collector->addSuggestion($suggestion);
        }

        self::assertTrue($collector->hasSuggestions());
        self::assertSame($suggestions, $collector->getSuggestions());
    }
}
