<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\RelationList;

use Ibexa\Contracts\Core\Repository\Values\Content\Relation;

interface RelationListItemInterface
{
    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation|null
     */
    public function getRelation(): ?Relation;

    /**
     * @phpstan-assert-if-true !null $this->getRelation()
     */
    public function hasRelation(): bool;
}
