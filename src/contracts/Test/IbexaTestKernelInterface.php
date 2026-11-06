<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test;

/**
 * @internal
 *
 * @experimental
 */
interface IbexaTestKernelInterface
{
    /**
     * @deprecated 6.0.0 will be removed in 7.0.0. Use {@see \Ibexa\Contracts\Test\Core\IbexaTestCore::getAliasServiceId()} from ibexa/test-core instead.
     *
     * @return string a service ID that service aliases will be registered as
     */
    public static function getAliasServiceId(string $id): string;
}
