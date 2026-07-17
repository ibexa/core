<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Strategy\ContentPublication;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;

final readonly class ContentPublicationResult
{
    public function __construct(
        public ?Content $publishedContent,
    ) {
    }
}
