<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration\Fixtures;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Migration4RequiresVisibleTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf(!$schema->hasTable('runner_test'), 'Table "runner_test" is not in the introspected schema.');

        $this->addSql('INSERT INTO runner_test (id) VALUES (2)');
    }
}
