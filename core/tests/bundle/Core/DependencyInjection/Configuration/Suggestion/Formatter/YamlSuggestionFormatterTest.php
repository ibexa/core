<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Configuration\Suggestion\Formatter;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\Suggestion\ConfigSuggestion;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\Suggestion\Formatter\YamlSuggestionFormatter;
use PHPUnit\Framework\TestCase;

class YamlSuggestionFormatterTest extends TestCase
{
    public function testFormat()
    {
        $message = <<<EOT
Database configuration has changed for Ibexa Content repository.
Please define:
 - An entry in ibexa.repositories
 - A Doctrine connection (You may check configuration reference for Doctrine "config:dump-reference doctrine" console command.)
 - A reference to configured repository in ibexa.system.foo.repository
EOT;
        $suggestion = new ConfigSuggestion($message);
        $suggestion->setMandatory(true);
        $suggestionArray = [
            'doctrine' => [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'driver' => 'pdo_mysql',
                            'host' => 'localhost',
                            'dbname' => 'my_database',
                            'user' => 'my_user',
                            'password' => 'some_password',
                            'charset' => 'UTF8',
                        ],
                    ],
                ],
            ],
            'ibexa' => [
                'repositories' => [
                    'my_repository' => ['engine' => 'legacy', 'connection' => 'default'],
                ],
                'system' => [
                    'foo' => [
                        'repository' => 'my_repository',
                    ],
                ],
            ],
        ];
        $suggestion->setSuggestion($suggestionArray);

        $expectedMessage = <<<EOT
Database configuration has changed for Ibexa Content repository.
Please define:
 - An entry in ibexa.repositories
 - A Doctrine connection (You may check configuration reference for Doctrine "config:dump-reference doctrine" console command.)
 - A reference to configured repository in ibexa.system.foo.repository


Example:
========

doctrine:
    dbal:
        connections:
            default:
                driver: pdo_mysql
                host: localhost
                dbname: my_database
                user: my_user
                password: some_password
                charset: UTF8
ibexa:
    repositories:
        my_repository:
            engine: legacy
            connection: default
    system:
        foo:
            repository: my_repository
EOT;

        $formatter = new YamlSuggestionFormatter();
        self::assertSame($expectedMessage, trim($formatter->format($suggestion)));
    }

    public function testFormatNoSuggestion()
    {
        $message = 'This is a message';
        $suggestion = new ConfigSuggestion($message);
        $formatter = new YamlSuggestionFormatter();
        self::assertSame($message, $formatter->format($suggestion));
    }
}
