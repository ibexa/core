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

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess */
    private $siteAccess;

    private SiteAccessServiceInterface $siteAccessService;

    /** @var bool */
    private $debug;

    public function __construct(
        string $defaultSiteAccessName,
        SiteAccess\SiteAccessProviderInterface $siteAccessProvider,
        SiteAccess $siteAccess,
        SiteAccessServiceInterface $siteAccessService,
        bool $debug = false
    ) {
        $this->defaultSiteAccessName = $defaultSiteAccessName;
        $this->siteAccessProvider = $siteAccessProvider;
        $this->siteAccess = $siteAccess;
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
        // Note: this mutates the shared SiteAccess singleton in place (rather than only calling
        // changeSiteAccess() below), because consumers that still read that singleton directly
        // (e.g. the config resolver chain, for its MATCHING_TYPE_UNINITIALIZED early-access guard)
        // never receive a SiteAccess through SiteAccessService and would otherwise never see it change.
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

        $this->siteAccessService->changeSiteAccess($this->siteAccess);
    }

    public function setDebug($debug = false)
    {
        $this->debug = $debug;
    }
}
