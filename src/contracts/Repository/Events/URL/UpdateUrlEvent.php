<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\URL;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\URL\URL;
use Ibexa\Contracts\Core\Repository\Values\URL\URLUpdateStruct;

final class UpdateUrlEvent extends AfterEvent
{
    private URL $url;

    private URLUpdateStruct $struct;

    private URL $updatedUrl;

    public function __construct(
        URL $updatedUrl,
        URL $url,
        URLUpdateStruct $struct
    ) {
        $this->url = $url;
        $this->struct = $struct;
        $this->updatedUrl = $updatedUrl;
    }

    public function getUrl(): URL
    {
        return $this->url;
    }

    public function getStruct(): URLUpdateStruct
    {
        return $this->struct;
    }

    public function getUpdatedUrl(): URL
    {
        return $this->updatedUrl;
    }
}
