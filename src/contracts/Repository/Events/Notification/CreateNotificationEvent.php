<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Notification;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Notification\CreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Notification\Notification;

final class CreateNotificationEvent extends AfterEvent
{
    private Notification $notification;

    private CreateStruct $createStruct;

    public function __construct(
        Notification $notification,
        CreateStruct $createStruct
    ) {
        $this->notification = $notification;
        $this->createStruct = $createStruct;
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }

    public function getCreateStruct(): CreateStruct
    {
        return $this->createStruct;
    }
}
