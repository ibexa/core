<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Suggestion;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Suggestion\ConfigSuggestion;
use PHPUnit\Framework\TestCase;

class ConfigSuggestionTest extends TestCase
{
    public function testEmptyConstructor()
    {
        $suggestion = new ConfigSuggestion();
        self::assertNull($suggestion->getMessage());
        self::assertSame([], $suggestion->getSuggestion());
        self::assertFalse($suggestion->isMandatory());
    }

    public function testConfigSuggestion()
    {
        $message = 'some message';
        $configArray = ['foo' => 'bar'];

        $suggestion = new ConfigSuggestion($message, $configArray);
        self::assertSame($message, $suggestion->getMessage());
        self::assertSame($configArray, $suggestion->getSuggestion());
        self::assertFalse($suggestion->isMandatory());

        $newMessage = 'foo bar';
        $suggestion->setMessage($newMessage);
        self::assertSame($newMessage, $suggestion->getMessage());

        $newConfigArray = ['ibexa' => 'publish'];
        $suggestion->setSuggestion($newConfigArray);
        self::assertSame($newConfigArray, $suggestion->getSuggestion());

        $suggestion->setMandatory(true);
        self::assertTrue($suggestion->isMandatory());
    }
}
