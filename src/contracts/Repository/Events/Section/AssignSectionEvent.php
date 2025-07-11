<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Section;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Section;

final class AssignSectionEvent extends AfterEvent
{
    private ContentInfo $contentInfo;

    private Section $section;

    public function __construct(
        ContentInfo $contentInfo,
        Section $section
    ) {
        $this->contentInfo = $contentInfo;
        $this->section = $section;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }

    public function getSection(): Section
    {
        return $this->section;
    }
}
