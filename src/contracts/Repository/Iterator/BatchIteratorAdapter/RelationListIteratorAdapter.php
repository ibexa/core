<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Iterator;
use IteratorIterator;

final class RelationListIteratorAdapter implements BatchIteratorAdapter
{
    public function __construct(
        readonly private ContentService $contentService,
        readonly private VersionInfo $versionInfo,
        readonly private ?RelationType $relationType = null,
    ) {
    }

    public function fetch(int $offset, int $limit): Iterator
    {
        $iterator = $this->contentService->loadRelationList(
            $this->versionInfo,
            $offset,
            $limit,
            $this->relationType
        )->getIterator();

        if ($iterator instanceof Iterator) {
            return $iterator;
        }

        return new IteratorIterator($iterator);
    }
}
