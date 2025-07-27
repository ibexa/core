<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Security;

use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils as BaseHttpUtils;

class HttpUtils extends BaseHttpUtils implements SiteAccessAware
{
    private ?SiteAccess $siteAccess;

    public function setSiteAccess(?SiteAccess $siteAccess = null): void
    {
        $this->siteAccess = $siteAccess;
    }

    private function analyzeLink(string $path): string
    {
        $matcher = $this->siteAccess?->matcher;
        if ($path[0] === '/' && $matcher instanceof SiteAccess\URILexer) {
            $path = $matcher->analyseLink($path);
        }

        return $path;
    }

    public function generateUri(Request $request, string $path): string
    {
        if ($this->isRouteName($path)) {
            // Remove SiteAccess attribute to avoid triggering reverse SiteAccess lookup during link generation.
            $request->attributes->remove('siteaccess');
        }

        return parent::generateUri($request, $this->analyzeLink($path));
    }

    public function checkRequestPath(Request $request, string $path): bool
    {
        return parent::checkRequestPath($request, $this->analyzeLink($path));
    }

    /**
     * @param string $path Path can be URI, absolute URL or a route name.
     *
     * @return bool
     */
    private function isRouteName(string $path): bool
    {
        return !empty($path) && !str_starts_with($path, 'http') && !str_starts_with($path, '/');
    }
}
