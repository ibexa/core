<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\ProxyFactory;

use Ibexa\Contracts\Core\Repository\Repository;

/**
 * @internal
 */
final class ProxyDomainMapperFactory implements ProxyDomainMapperFactoryInterface
{
    private ProxyGeneratorInterface $proxyGenerator;

    public function __construct(ProxyGeneratorInterface $proxyGenerator)
    {
        $this->proxyGenerator = $proxyGenerator;
    }

    public function create(Repository $repository): ProxyDomainMapperInterface
    {
        return new ProxyDomainMapper($repository, $this->proxyGenerator);
    }
}
