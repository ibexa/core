<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test\Persistence;

/**
 * A fixture whose rows are added to whatever the tables already hold, instead of replacing it.
 *
 * {@see \Ibexa\Contracts\Core\Test\Persistence\Fixture\FixtureImporter} normally truncates every
 * table a fixture touches before inserting, because a plain fixture is assumed to be the sole
 * author of those tables. That assumption breaks when the baseline content arrives some other way
 * - notably when the Doctrine Migrations install path has already inserted it via
 * ImportDataMigration - since truncating would destroy it.
 *
 * Mark a fixture with this interface when it contributes extra rows to tables that something else
 * has already populated. A fixture that owns its tables outright should stay a plain
 * {@see Fixture}: truncating is what keeps repeated runs deterministic.
 *
 * @internal for internal use by Repository test setup
 */
interface AppendOnlyFixture extends Fixture
{
}
