<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Message;

final readonly class PublishContentAsync
{
    public function __construct(
        public int $contentId,
        public int $versionNo,
        /** @var list<string> */
        public array $translations,
    ) {
    }
}
