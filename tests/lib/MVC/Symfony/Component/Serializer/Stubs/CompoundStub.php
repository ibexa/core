<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer\Stubs;

use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound;

/**
 * @phpstan-import-type TCompoundMatcherConfig from \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Compound
 */
final class CompoundStub extends Compound
{
    /**
     * @param Matcher[] $subMatchers
     */
    public function __construct(array $subMatchers)
    {
        parent::__construct([]);
        $this->subMatchers = $subMatchers;
    }

    /**
     * @throws NotImplementedException
     */
    public function match(): never
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @throws NotImplementedException
     */
    public function reverseMatch(string $siteAccessName): never
    {
        throw new NotImplementedException(__METHOD__);
    }
}
