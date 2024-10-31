<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

/**
 * @internal
 */
interface RelationListFacade
{
    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     */
    public function getRelations(VersionInfo $versionInfo): iterable;
}
