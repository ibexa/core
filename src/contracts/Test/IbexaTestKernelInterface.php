<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test;

use Ibexa\Contracts\Core\Test\Persistence\Fixture;

/**
 * @internal
 *
 * @experimental
 */
interface IbexaTestKernelInterface
{
    /**
     * @deprecated 5.0.12 will be removed in 6.0.0. Use {@see \Ibexa\Contracts\Test\Core\IbexaTestCore::getAliasServiceId()} from ibexa/test-core instead.
     *
     * @return string a service ID that service aliases will be registered as
     */
    public static function getAliasServiceId(string $id): string;

    /**
     * @return iterable<string>
     */
    public function getSchemaFiles(): iterable;

    /**
     * @return iterable<Fixture>
     */
    public function getFixtures(): iterable;
}
