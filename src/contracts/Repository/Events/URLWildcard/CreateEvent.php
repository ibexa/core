<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\URLWildcard;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;

final class CreateEvent extends AfterEvent
{
    private string $sourceUrl;

    private string $destinationUrl;

    private bool $forward;

    private URLWildcard $urlWildcard;

    public function __construct(
        URLWildcard $urlWildcard,
        string $sourceUrl,
        string $destinationUrl,
        bool $forward
    ) {
        $this->sourceUrl = $sourceUrl;
        $this->destinationUrl = $destinationUrl;
        $this->forward = $forward;
        $this->urlWildcard = $urlWildcard;
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
        return $this->urlWildcard;
    }
}
