<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Helper;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Iterator\BatchIterator;
use Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter\RelationListIteratorAdapter;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

final class RelationListHelper
{
    public function __construct(
        private readonly ContentService $contentService
    ) {
    }

    /**
     * @return \Ibexa\Core\Repository\Values\Content\Relation[]
     */
    public function getRelations(VersionInfo $versionInfo): array
    {
        $relationListIterator = new BatchIterator(
            new RelationListIteratorAdapter(
                $this->contentService,
                $versionInfo
            )
        );

        $relations = [];
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface $relationListItem */
        foreach ($relationListIterator as $relationListItem) {
            if ($relationListItem->hasRelation()) {
                /** @var \Ibexa\Core\Repository\Values\Content\Relation $relation */
                $relation = $relationListItem->getRelation();
                $relations[] = $relation;
            }
        }

        return $relations;
    }
}
