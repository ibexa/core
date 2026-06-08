<?php

declare(strict_types=1);

namespace Ibexa\Bundle\Core\Message;

use Ibexa\Contracts\Core\Repository\Values\Content\Language;

final readonly class PublishContentAsync
{
    public function __construct(
        public int $contentId,
        public int $versionNo,
        array $translations = Language::ALL,
    )
    {
    }
}
