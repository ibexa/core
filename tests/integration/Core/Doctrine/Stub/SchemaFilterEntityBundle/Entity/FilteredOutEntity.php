<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Doctrine\Stub\SchemaFilterEntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Backed by a table that is ORM-managed, and therefore allowed by
 * ManagedTablesSchemaAssetFilter, but excluded by the schema filter the test
 * kernel configures on behalf of a project.
 */
#[ORM\Entity]
#[ORM\Table(name: 'ibexa_test_filtered_out_entity')]
class FilteredOutEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;
}
