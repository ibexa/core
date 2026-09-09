<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test;

/**
 * @internal
 * @experimental
 */
interface IbexaTestKernelInterface
{
    /**
     * @return string a service ID that service aliases will be registered as
     */
    public static function getAliasServiceId(string $id): string;

    /**
     * @return iterable<string>
     *
     * @deprecated since Ibexa 4.6.x. Test schema is built from
     *             {@see \Ibexa\Contracts\DoctrineSchema\Event\SchemaBuilderEvent} — the same path
     *             `ibexa:install` uses — so a kernel no longer declares which schema files to load;
     *             whichever bundles it registers is what the schema contains. Implementations may
     *             return an empty iterable. Will be removed in 6.0.
     */
    public function getSchemaFiles(): iterable;

    /**
     * @return iterable<\Ibexa\Contracts\Core\Test\Persistence\Fixture>
     */
    public function getFixtures(): iterable;
}
