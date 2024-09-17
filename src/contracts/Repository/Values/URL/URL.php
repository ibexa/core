<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\URL;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

class URL extends ValueObject
{
    /**
     * The unique id of the URL.
     *
     * @var int
     */
    protected $id;

    /**
     * URL itself e.g. "http://ibexa.co".
     *
     * @var string
     */
    protected $url;

    /**
     * Is URL valid ?
     *
     * @var bool
     */
    protected $isValid;

    /**
     * Date of last check.
     *
     * @var \DateTimeInterface
     */
    protected $lastChecked;

    /**
     * Creation date.
     *
     * @var \DateTimeInterface
     */
    protected $created;

    /**
     * Modified date.
     *
     * @var \DateTimeInterface
     */
    protected $modified;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getLastChecked(): ?DateTimeInterface
    {
        return $this->lastChecked;
    }

    public function getCreated(): ?DateTimeInterface
    {
        return $this->created;
    }

    public function getModified(): ?DateTimeInterface
    {
        return $this->modified;
    }
}
