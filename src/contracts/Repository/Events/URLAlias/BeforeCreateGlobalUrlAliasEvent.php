<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\URLAlias;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use UnexpectedValueException;

final class BeforeCreateGlobalUrlAliasEvent extends BeforeEvent
{
    private $resource;

    private $path;

    private $languageCode;

    private $forwarding;

    private $alwaysAvailable;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\URLAlias|null */
    private $urlAlias;

    public function __construct($resource, $path, $languageCode, $forwarding, $alwaysAvailable)
    {
        $this->resource = $resource;
        $this->path = $path;
        $this->languageCode = $languageCode;
        $this->forwarding = $forwarding;
        $this->alwaysAvailable = $alwaysAvailable;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    public function getForwarding()
    {
        return $this->forwarding;
    }

    public function getAlwaysAvailable()
    {
        return $this->alwaysAvailable;
    }

    public function getUrlAlias(): URLAlias
    {
        if (!$this->hasUrlAlias()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasUrlAlias() or set it using setUrlAlias() before you call the getter.', URLAlias::class));
        }

        return $this->urlAlias;
    }

    public function setUrlAlias(?URLAlias $urlAlias): void
    {
        $this->urlAlias = $urlAlias;
    }

    public function hasUrlAlias(): bool
    {
        return $this->urlAlias instanceof URLAlias;
    }
}
