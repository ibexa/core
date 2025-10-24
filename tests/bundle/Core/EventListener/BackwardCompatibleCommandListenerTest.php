<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\Command\BackwardCompatibleCommand;
use Ibexa\Bundle\Core\EventListener\BackwardCompatibleCommandListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;

final class BackwardCompatibleCommandListenerTest extends TestCase
{
    private const string MORE_THAN_2_WHITESPACES_AND_NEW_LINES = '/\s{2,}|\\n/';

    private const string EXAMPLE_NAME = 'ibexa:command';
    private const array EXAMPLE_DEPRECATED_ALIASES = [
        'ezplatform:command',
        'ezplatform-ee:command',
        'ezstudio:command',
        'ezpublish-platform:command',
        'ezpublish:command',
    ];

    private BackwardCompatibleCommandListener $listener;

    protected function setUp(): void
    {
        $this->listener = new BackwardCompatibleCommandListener();
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                ConsoleEvents::COMMAND => [['onConsoleCommand', 128]],
            ],
            $this->listener::getSubscribedEvents()
        );
    }

    public function testDeprecationWarningIsSkippedForNonBackwardCompatibleCommand(): void
    {
        $command = $this->createCommand(self::EXAMPLE_NAME);

        $input = $this->createCommandInput(self::EXAMPLE_NAME);
        $output = new BufferedOutput();

        $this->listener->onConsoleCommand(new ConsoleCommandEvent($command, $input, $output));

        $this->assertOutputNotContainsDeprecationWarning($output);
    }

    public function testDeprecationWarningIsSkippedForCurrentCommandName(): void
    {
        $command = $this->createBackwardCompatibleCommand(
            self::EXAMPLE_NAME,
            self::EXAMPLE_DEPRECATED_ALIASES
        );

        $input = $this->createCommandInput(self::EXAMPLE_NAME);
        $output = new BufferedOutput();

        $this->listener->onConsoleCommand(new ConsoleCommandEvent($command, $input, $output));

        $this->assertOutputNotContainsDeprecationWarning($output);
    }

    public function testDeprecationWarningIsEmittedForDeprecatedAlias(): void
    {
        $command = $this->createBackwardCompatibleCommand(
            self::EXAMPLE_NAME,
            self::EXAMPLE_DEPRECATED_ALIASES
        );

        $input = $this->createCommandInput(self::EXAMPLE_DEPRECATED_ALIASES[0]);
        $output = new BufferedOutput();

        $this->listener->onConsoleCommand(new ConsoleCommandEvent($command, $input, $output));

        $this->assertOutputContainsDeprecationWarning($output);
    }

    private function assertOutputNotContainsDeprecationWarning(BufferedOutput $output): void
    {
        // Output buffer should be empty
        self::assertEquals('', $output->fetch());
    }

    private function assertOutputContainsDeprecationWarning(BufferedOutput $output): void
    {
        $outputString = trim(preg_replace(self::MORE_THAN_2_WHITESPACES_AND_NEW_LINES, ' ', $output->fetch()) ?? '');

        self::assertEquals(
            '[WARNING] Command alias "ezplatform:command" is deprecated since 3.3 and will be removed in in 4.0. Use "ibexa:command" instead.',
            $outputString
        );
    }

    /**
     * @param string[] $aliases
     */
    private function createBackwardCompatibleCommand(
        string $name,
        array $aliases = []
    ): Command {
        return new class($name, $aliases) extends Command implements BackwardCompatibleCommand {
            /** @var string[] */
            private array $deprecatedAliases;

            /**
             * @param string[] $deprecatedAliases
             */
            public function __construct(
                string $name,
                array $deprecatedAliases
            ) {
                $this->deprecatedAliases = $deprecatedAliases;

                parent::__construct($name);
            }

            protected function configure(): void
            {
                $this->setAliases($this->deprecatedAliases);
            }

            public function getDeprecatedAliases(): array
            {
                return $this->deprecatedAliases;
            }
        };
    }

    private function createCommand(string $name): Command
    {
        return new Command($name);
    }

    private function createCommandInput(string $name): ArrayInput
    {
        return new ArrayInput(
            [
                'command' => $name,
            ],
            new InputDefinition([
                new InputArgument('command', InputArgument::REQUIRED),
            ])
        );
    }
}
