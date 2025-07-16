<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Cache\Resolver;

use Liip\ImagineBundle\Imagine\Cache\Resolver\ProxyResolver as ImagineProxyResolver;

class ProxyResolver extends ImagineProxyResolver
{
    /**
     * Replaces host with given proxy host.
     *
     * The original method from `\Liip\ImagineBundle\Imagine\Cache\Resolver\ProxyResolver::rewriteUrl()`
     * doesn't behave correctly when working with domain and port or with host which contains trailing slash.
     *
     * @see \Liip\ImagineBundle\Imagine\Cache\Resolver\ProxyResolver::rewriteUrl
     *
     * @param string $url
     */
    protected function rewriteUrl($url): string
    {
        if (empty($this->hosts)) {
            return $url;
        }

        $proxyHost = rtrim(reset($this->hosts), '/');
        $relativeUrl = parse_url($url, PHP_URL_PATH);

        return $proxyHost . $relativeUrl;
    }
}
