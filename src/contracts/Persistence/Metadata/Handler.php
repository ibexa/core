<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Metadata;

use Ibexa\Contracts\Core\Repository\Values\Content\Metadata;

/**
 * @internal
 */
interface Handler
{
    public function persist(
        Metadata $metadata
    ): void;
}

class_alias(Handler::class, 'eZ\Publish\SPI\Persistence\Metadata\Handler');
