<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\URLWildcard;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardUpdateStruct;

final class BeforeUpdateEvent extends BeforeEvent
{
    private URLWildcard $urlWildcard;

    private URLWildcardUpdateStruct $updateStruct;

    public function __construct(
        URLWildcard $urlWildcard,
        URLWildcardUpdateStruct $updateStruct
    ) {
        $this->urlWildcard = $urlWildcard;
        $this->updateStruct = $updateStruct;
    }

    public function getUrlWildcard(): URLWildcard
    {
        return $this->urlWildcard;
    }

    public function getUpdateStruct(): URLWildcardUpdateStruct
    {
        return $this->updateStruct;
    }
}
