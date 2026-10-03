<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\SchemaParity\Core;

use Ibexa\Contracts\Test\Core\Schema\AbstractSchemaParityTestCase;

/**
 * Checks that the Doctrine Migrations end with the tables the legacy schema.yaml declares, from an
 * empty database, from a legacy install, and from every older version of schema.yaml found at the
 * release tags (run `composer test-schema-parity` after fetching them).
 */
final class SchemaParityTest extends AbstractSchemaParityTestCase
{
    protected static function getSchemaFileHistory(): array
    {
        return ['v4.6.*' => 'src/bundle/Core/Resources/config/storage/legacy/schema.yaml'];
    }
}
