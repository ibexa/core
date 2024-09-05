<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event emitted after action execution.
 *
 * @link https://github.com/symfony/symfony/blob/5.4/src/Symfony/Contracts/EventDispatcher/Event.php Symfony\Contracts\EventDispatcher\Event
 */
abstract class AfterEvent extends Event
{
}

class_alias(AfterEvent::class, 'eZ\Publish\SPI\Repository\Event\AfterEvent');
