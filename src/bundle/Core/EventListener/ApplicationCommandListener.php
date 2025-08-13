<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\EventListener;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ApplicationCommandListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => [
                ['onConsoleCommand', 256],
            ],
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command === null) {
            return;
        }

        $application = $command->getApplication();
        if ($application === null) {
            return;
        }

        $inputDefinition = $application->getDefinition();
        if (false === $inputDefinition->hasOption('siteaccess')) {
            $inputDefinition->addOption(new InputOption(
                'siteaccess',
                null,
                InputOption::VALUE_OPTIONAL,
                'SiteAccess to use for operations. If not provided, default siteaccess will be used',
            ));
        }
    }
}
