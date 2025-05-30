<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Content;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;

final class DeleteContentEvent extends AfterEvent
{
    /** @var int[] */
    private array $locations;

    private ContentInfo $contentInfo;

    /**
     * @param int[] $locations
     */
    public function __construct(
        array $locations,
        ContentInfo $contentInfo
    ) {
        $this->locations = $locations;
        $this->contentInfo = $contentInfo;
    }

    /**
     * @return int[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }
}
