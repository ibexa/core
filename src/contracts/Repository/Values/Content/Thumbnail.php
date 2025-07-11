<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * @property-read string $resource
 * @property-read int|null $width
 * @property-read int|null $height
 * @property-read string|null $mimeType
 */
class Thumbnail extends ValueObject
{
    /**
     * Can be target URL or Base64 data (or anything else).
     */
    protected string $resource;

    protected ?int $width = null;

    protected ?int $height = null;

    protected ?string $mimeType = null;
}
