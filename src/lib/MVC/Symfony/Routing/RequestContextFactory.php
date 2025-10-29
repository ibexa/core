<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Routing;

use Ibexa\Bundle\Core\Routing\DefaultRouter;
use Symfony\Component\Routing\RequestContext;

/**
 * @internal
 *
 * @see DefaultRouter
 * @see Generator
 *
 * Encapsulates shared logic between Router and URL generator, meant to be lightweight and instantiated on the fly.
 */
final class RequestContextFactory
{
    private RequestContext $requestContext;

    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = clone $requestContext;
    }

    /**
     * Merges context from $simplifiedRequest into a clone of the current context.
     */
    public function getContextBySimplifiedRequest(SimplifiedRequest $simplifiedRequest): RequestContext
    {
        if ($simplifiedRequest->getScheme()) {
            $this->requestContext->setScheme($simplifiedRequest->getScheme());
        }

        if ($simplifiedRequest->getPort()) {
            if ($simplifiedRequest->getScheme() === 'https') {
                $this->requestContext->setHttpsPort((int)$simplifiedRequest->getPort());
            } else {
                $this->requestContext->setHttpPort((int)$simplifiedRequest->getPort());
            }
        }

        if ($simplifiedRequest->getHost()) {
            $this->requestContext->setHost($simplifiedRequest->getHost());
        }

        if ($simplifiedRequest->getPathInfo()) {
            $this->requestContext->setPathInfo($simplifiedRequest->getPathInfo());
        }

        return $this->requestContext;
    }
}
