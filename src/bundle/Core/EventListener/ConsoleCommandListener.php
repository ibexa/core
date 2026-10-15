<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\EventListener;

use Ibexa\Core\MVC\Exception\InvalidSiteAccessException;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleCommandListener implements EventSubscriberInterface, SiteAccessAware
{
    /** @var string */
    private $defaultSiteAccessName;

    /** @var SiteAccessProviderInterface */
    private $siteAccessProvider;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var SiteAccess|null */
    private $siteAccess;

    /** @var bool */
    private $debug;

    public function __construct(
        string $defaultSiteAccessName,
        SiteAccessProviderInterface $siteAccessProvider,
        EventDispatcherInterface $eventDispatcher,
        bool $debug = false
    ) {
        $this->defaultSiteAccessName = $defaultSiteAccessName;
        $this->siteAccessProvider = $siteAccessProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->debug = $debug;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => [
                ['onConsoleCommand', 128],
            ],
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $this->siteAccess->name = $event->getInput()->getParameterOption('--siteaccess', $this->defaultSiteAccessName);
        $this->siteAccess->matchingType = 'cli';

        if (!$this->siteAccessProvider->isDefined($this->siteAccess->name)) {
            throw new InvalidSiteAccessException(
                $this->siteAccess->name,
                $this->siteAccessProvider,
                $this->siteAccess->matchingType,
                $this->debug
            );
        }

        $this->eventDispatcher->dispatch(new ScopeChangeEvent($this->siteAccess), MVCEvents::CONFIG_SCOPE_CHANGE);
    }

    public function setSiteAccess(?SiteAccess $siteAccess = null)
    {
        $this->siteAccess = $siteAccess;
    }

    public function setDebug($debug = false)
    {
        $this->debug = $debug;
    }
}
