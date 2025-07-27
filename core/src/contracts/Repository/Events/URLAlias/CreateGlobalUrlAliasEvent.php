<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\URLAlias;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;

final class CreateGlobalUrlAliasEvent extends AfterEvent
{
    private string $resource;

    private string $path;

    private string $languageCode;

    private bool $forwarding;

    private bool $alwaysAvailable;

    private URLAlias $urlAlias;

    public function __construct(
        URLAlias $urlAlias,
        $resource,
        $path,
        $languageCode,
        $forwarding,
        $alwaysAvailable
    ) {
        $this->resource = $resource;
        $this->path = $path;
        $this->languageCode = $languageCode;
        $this->forwarding = $forwarding;
        $this->alwaysAvailable = $alwaysAvailable;
        $this->urlAlias = $urlAlias;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getForwarding(): bool
    {
        return $this->forwarding;
    }

    public function getAlwaysAvailable(): bool
    {
        return $this->alwaysAvailable;
    }

    public function getUrlAlias(): URLAlias
    {
        return $this->urlAlias;
    }
}
