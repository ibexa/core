<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony;

use Ibexa\Core\MVC\Symfony\Event\APIContentExceptionEvent;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\Event\PreContentViewEvent;
use Ibexa\Core\MVC\Symfony\Event\RouteReferenceGenerationEvent;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\View\Manager;

final class MVCEvents
{
    /**
     * The SITEACCESS event occurs after the SiteAccess matching has occurred.
     * This event gives further control on the matched SiteAccess.
     *
     * The event listener method receives a {@see PostSiteAccessMatchEvent}
     */
    public const SITEACCESS = SiteAccess::class;

    /**
     * The PRE_CONTENT_VIEW event occurs right before a view is rendered for a content, via the content view controller.
     * This event is triggered by the view manager and allows you to inject additional parameters to the content view template.
     *
     * The event listener method receives a {@see PreContentViewEvent}
     *
     * @see Manager
     */
    public const PRE_CONTENT_VIEW = 'ezpublish.pre_content_view';

    /**
     * The API_CONTENT_EXCEPTION event occurs when the API throws an exception that could not be caught internally
     * (missing field type, internal error...).
     * It allows further programmatic handling (like rendering a custom view) for the exception thrown.
     *
     * The event listener method receives an {@see APIContentExceptionEvent}.
     */
    public const API_CONTENT_EXCEPTION = 'ezpublish.api.contentException';

    /**
     * CONFIG_SCOPE_CHANGE event occurs when configuration scope is changed (e.g. for content preview in a given siteaccess).
     *
     * The event listener method receives a {@see ScopeChangeEvent} instance.
     */
    public const CONFIG_SCOPE_CHANGE = 'ezpublish.config.scope_change';

    /**
     * CONFIG_SCOPE_RESTORE event occurs when original configuration scope is restored.
     * It always happens after a scope change (see CONFIG_SCOPE_CHANGE).
     *
     * The event listener method receives a {@see ScopeChangeEvent} instance.
     */
    public const CONFIG_SCOPE_RESTORE = 'ezpublish.config.scope_restore';

    /**
     * ROUTE_REFERENCE_GENERATION event occurs when a RouteReference is generated, and gives an opportunity to
     * alter the RouteReference, e.g. by adding parameters.
     *
     * The event listener method receives a {@see RouteReferenceGenerationEvent} instance.
     */
    public const ROUTE_REFERENCE_GENERATION = 'ezpublish.routing.reference_generation';
}
