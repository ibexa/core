<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\UserMetadataTermAggregation;
use Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\DataSetBuilder\TermAggregationDataSetBuilder;

final class UserMetadataTermAggregationTest extends AbstractAggregationTestCase
{
    public static function dataProviderForTestFindContentWithAggregation(): iterable
    {
        yield '::OWNER' => self::createOwnerTermAggregationDataSet();
        yield '::GROUP' => self::createGroupTermAggregationDataSet();
        yield '::MODIFIER' => self::createModifierTermAggregationDataSet();
    }

    public static function dataProviderForTestFindLocationWithAggregation(): iterable
    {
        yield from static::dataProviderForTestFindContentWithAggregation();
    }

    private static function createOwnerTermAggregationDataSet(): array
    {
        $aggregation = new UserMetadataTermAggregation('owner', UserMetadataTermAggregation::OWNER);

        $builder = new TermAggregationDataSetBuilder($aggregation);
        $builder->setExpectedEntries(['admin' => 18]);
        $builder->setEntryMapper([static::resolveRepository()->getUserService(), 'loadUserByLogin']);

        return $builder->build();
    }

    private static function createGroupTermAggregationDataSet(): array
    {
        $aggregation = new UserMetadataTermAggregation('user_group', UserMetadataTermAggregation::GROUP);

        $builder = new TermAggregationDataSetBuilder($aggregation);
        $builder->setExpectedEntries([
            12 => 18,
            14 => 18,
            4 => 18,
        ]);
        $builder->setEntryMapper([static::resolveRepository()->getUserService(), 'loadUserGroup']);

        return $builder->build();
    }

    private static function createModifierTermAggregationDataSet(): array
    {
        $aggregation = new UserMetadataTermAggregation('modifier', UserMetadataTermAggregation::MODIFIER);

        $builder = new TermAggregationDataSetBuilder($aggregation);
        $builder->setExpectedEntries(['admin' => 18]);
        $builder->setEntryMapper([static::resolveRepository()->getUserService(), 'loadUserByLogin']);

        return $builder->build();
    }
}
