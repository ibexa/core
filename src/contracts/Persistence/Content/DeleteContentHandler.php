<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Persistence\Content;

interface DeleteContentHandler
{
    public function preDeleteContent(ContentInfo $contentInfo, ?int $mainLocationId): void;

    public function postDeleteContent(ContentInfo $contentInfo, ?int $mainLocationId): void;
}
