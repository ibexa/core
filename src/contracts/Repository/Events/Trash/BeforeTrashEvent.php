<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Trash;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\TrashItem;
use UnexpectedValueException;

final class BeforeTrashEvent extends BeforeEvent
{
    private Location $location;

    private ?TrashItem $result = null;

    private bool $resultSet = false;

    public function __construct(Location $location)
    {
        $this->location = $location;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getResult(): ?TrashItem
    {
        if (!$this->isResultSet()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s (or null). Check isResultSet() or set it using setResult() before you call the getter.', TrashItem::class));
        }

        return $this->result;
    }

    public function setResult(?TrashItem $result): void
    {
        $this->result = $result;
        $this->resultSet = true;
    }

    public function hasTrashItem(): bool
    {
        return $this->result instanceof TrashItem;
    }

    public function resetResult(): void
    {
        $this->result = null;
        $this->resultSet = false;
    }

    public function isResultSet(): bool
    {
        return $this->resultSet;
    }
}
