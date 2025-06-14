<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\ObjectState;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupCreateStruct;

final class CreateObjectStateGroupEvent extends AfterEvent
{
    private ObjectStateGroup $objectStateGroup;

    private ObjectStateGroupCreateStruct $objectStateGroupCreateStruct;

    public function __construct(
        ObjectStateGroup $objectStateGroup,
        ObjectStateGroupCreateStruct $objectStateGroupCreateStruct
    ) {
        $this->objectStateGroup = $objectStateGroup;
        $this->objectStateGroupCreateStruct = $objectStateGroupCreateStruct;
    }

    public function getObjectStateGroup(): ObjectStateGroup
    {
        return $this->objectStateGroup;
    }

    public function getObjectStateGroupCreateStruct(): ObjectStateGroupCreateStruct
    {
        return $this->objectStateGroupCreateStruct;
    }
}
