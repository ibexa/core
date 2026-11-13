<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Bundle\RepositoryInstaller\Migration\AddUrlAliasAlwaysAvailableColumnMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\MigrationRunContext;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;

#[CoversClass(AddUrlAliasAlwaysAvailableColumnMigration::class)]
final class AddUrlAliasAlwaysAvailableColumnMigrationTest extends TestCase
{
    public function testBackfillsTheFlagInChunksById(): void
    {
        $connection = $this->getDatabaseConnection();
        foreach ([1 => ['foo', 'acbd18db4cc2f85cedef654fccc4a4d8', 3], 5001 => ['bar', '37b51d194a7513e45b56f6524f2d51f2', 2]] as $id => [$text, $textMd5, $langMask]) {
            $connection->insert('ibexa_url_alias_ml', [
                'parent' => 0,
                'text_md5' => $textMd5,
                'id' => $id,
                'text' => $text,
                'action' => 'eznode:' . $id,
                'action_type' => 'eznode',
                'lang_mask' => $langMask,
            ], ['lang_mask' => ParameterType::INTEGER]);
        }

        $migration = new AddUrlAliasAlwaysAvailableColumnMigration($connection, new NullLogger(), new MigrationRunContext());
        $migration->up(new Schema());
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }

        self::assertCount(2, $migration->getSql());
        self::assertEquals(
            [1 => 1, 5001 => 0],
            $connection->fetchAllKeyValue('SELECT id, is_always_available FROM ibexa_url_alias_ml ORDER BY id')
        );
    }
}
