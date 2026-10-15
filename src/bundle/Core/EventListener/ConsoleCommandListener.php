<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\EventListener;

use Ibexa\Core\MVC\Exception\InvalidSiteAccessException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleCommandListener implements EventSubscriberInterface
{
    /** @var string */
    private $defaultSiteAccessName;

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface */
    private $siteAccessProvider;

    private SiteAccessServiceInterface $siteAccessService;

    /** @var bool */
    private $debug;

    public function __construct(
        string $defaultSiteAccessName,
        SiteAccess\SiteAccessProviderInterface $siteAccessProvider,
        SiteAccessServiceInterface $siteAccessService,
        bool $debug = false
    ) {
        $this->defaultSiteAccessName = $defaultSiteAccessName;
        $this->siteAccessProvider = $siteAccessProvider;
        $this->siteAccessService = $siteAccessService;
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
        $siteAccessName = $event->getInput()->getParameterOption('--siteaccess', $this->defaultSiteAccessName);
        $siteAccess = new SiteAccess($siteAccessName, 'cli');

        if (!$this->siteAccessProvider->isDefined($siteAccess->name)) {
            throw new InvalidSiteAccessException(
                $siteAccess->name,
                $this->siteAccessProvider,
                $siteAccess->matchingType,
                $this->debug
            );
        }

        $this->siteAccessService->changeSiteAccess($siteAccess);
    }

    public function setDebug($debug = false)
    {
        $this->debug = $debug;
    }
}
