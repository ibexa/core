<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\URLWildcard;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;
use UnexpectedValueException;

final class BeforeCreateEvent extends BeforeEvent
{
    private string $sourceUrl;

    private string $destinationUrl;

    private bool $forward;

    private ?URLWildcard $urlWildcard = null;

    public function __construct(string $sourceUrl, string $destinationUrl, bool $forward)
    {
        $this->sourceUrl = $sourceUrl;
        $this->destinationUrl = $destinationUrl;
        $this->forward = $forward;
    }

    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function getDestinationUrl(): string
    {
        return $this->destinationUrl;
    }

    public function getForward(): bool
    {
        return $this->forward;
    }

    public function getUrlWildcard(): URLWildcard
    {
        if (!$this->hasUrlWildcard()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasUrlWildcard() or set it using setUrlWildcard() before you call the getter.', URLWildcard::class));
        }

        return $this->urlWildcard;
    }

    public function setUrlWildcard(?URLWildcard $urlWildcard): void
    {
        $this->urlWildcard = $urlWildcard;
    }

    public function hasUrlWildcard(): bool
    {
        return $this->urlWildcard instanceof URLWildcard;
    }
}
