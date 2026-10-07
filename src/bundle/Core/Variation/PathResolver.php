<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Variation;

use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Symfony\Component\Routing\RequestContext;

abstract class PathResolver
{
    protected RequestContext $requestContext;

    protected VariationPathGenerator $variationPathGenerator;

    public function __construct(
        RequestContext $requestContext,
        VariationPathGenerator $variationPathGenerator
    ) {
        $this->requestContext = $requestContext;
        $this->variationPathGenerator = $variationPathGenerator;
    }

    abstract public function resolve(
        $path,
        $variation
    ): string;

    /**
     * Returns path for filtered image from original path, using the VariationPathGenerator.
     *
     * @return string
     */
    public function getFilePath(
        ?string $path,
        string $variation
    ) {
        return $this->variationPathGenerator->getVariationPath($path, $variation);
    }

    /**
     * Returns base URL, with scheme, host and port, for current request context.
     * If no delivery URL is configured for current SiteAccess, will return base URL from current RequestContext.
     */
    protected function getBaseUrl(): string
    {
        $port = '';
        if ($this->requestContext->getScheme() === 'https' && $this->requestContext->getHttpsPort() != 443) {
            $port = ":{$this->requestContext->getHttpsPort()}";
        }

        if ($this->requestContext->getScheme() === 'http' && $this->requestContext->getHttpPort() != 80) {
            $port = ":{$this->requestContext->getHttpPort()}";
        }

        $baseUrl = $this->requestContext->getBaseUrl();
        if (substr($this->requestContext->getBaseUrl(), -4) === '.php') {
            $baseUrl = pathinfo($this->requestContext->getBaseurl(), PATHINFO_DIRNAME);
        }
        $baseUrl = rtrim($baseUrl, '/\\');

        return sprintf(
            '%s://%s%s%s',
            $this->requestContext->getScheme(),
            $this->requestContext->getHost(),
            $port,
            $baseUrl
        );
    }
}
