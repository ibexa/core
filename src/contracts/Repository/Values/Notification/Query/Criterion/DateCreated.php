<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\CriterionInterface;

final class DateCreated implements CriterionInterface
{
    private ?DateTimeInterface $from;

    private ?DateTimeInterface $to;

    public function __construct(?DateTimeInterface $from = null, ?DateTimeInterface $to = null)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): ?DateTimeInterface
    {
        return $this->from;
    }

    public function setFrom(?DateTimeInterface $from): void
    {
        $this->from = $from;
    }

    public function getTo(): ?DateTimeInterface
    {
        return $this->to;
    }

    public function setTo(?DateTimeInterface $to): void
    {
        $this->to = $to;
    }
}
