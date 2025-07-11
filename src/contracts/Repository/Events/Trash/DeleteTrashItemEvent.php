<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Trash;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResult;
use Ibexa\Contracts\Core\Repository\Values\Content\TrashItem;

final class DeleteTrashItemEvent extends AfterEvent
{
    private TrashItem $trashItem;

    private TrashItemDeleteResult $result;

    public function __construct(
        TrashItemDeleteResult $result,
        TrashItem $trashItem
    ) {
        $this->trashItem = $trashItem;
        $this->result = $result;
    }

    public function getTrashItem(): TrashItem
    {
        return $this->trashItem;
    }

    public function getResult(): TrashItemDeleteResult
    {
        return $this->result;
    }
}
