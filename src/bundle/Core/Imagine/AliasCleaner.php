<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine;

use Ibexa\Core\FieldType\Image\AliasCleanerInterface;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;

class AliasCleaner implements AliasCleanerInterface
{
    private ResolverInterface $aliasResolver;

    public function __construct(ResolverInterface $aliasResolver)
    {
        $this->aliasResolver = $aliasResolver;
    }

    public function removeAliases($originalPath): void
    {
        $this->aliasResolver->remove([$originalPath], []);
    }
}
