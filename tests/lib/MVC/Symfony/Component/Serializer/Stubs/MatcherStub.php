<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Component\Serializer\Stubs;

use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

final class MatcherStub implements Matcher
{
    private mixed $data;

    public function __construct(mixed $data = null)
    {
        $this->data = $data;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function setRequest(SimplifiedRequest $request): never
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function match(): never
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function getName(): never
    {
        throw new NotImplementedException(__METHOD__);
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
