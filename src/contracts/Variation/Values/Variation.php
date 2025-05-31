<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Variation\Values;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * Base class for file variations (i.e. image aliases).
 *
 * @property-read int $fileSize Number of bytes for current variation
 * @property-read string $mimeType The MIME type (for example "image/png")
 * @property-read string $fileName The name of the file (for example "my_image.png")
 * @property-read string $dirPath The path to the file (for example "var/storage/images/test/199-2-eng-GB")
 * @property-read string $uri Complete path + name of image file (for example "var/storage/images/test/199-2-eng-GB/apple.png")
 * @property-read \DateTimeInterface $lastModified When the variation was last modified
 */
class Variation extends ValueObject
{
    /**
     * Number of bytes for current variation.
     */
    protected int $fileSize;

    /**
     * The MIME type (for example "image/png").
     */
    protected string $mimeType;

    /**
     * The name of the file (for example "my_image.png").
     */
    protected string $fileName;

    /**
     * The path to the file (for example "var/storage/images/test/199-2-eng-GB").
     */
    protected string $dirPath;

    /**
     * Complete path + name of image file (for example "var/storage/images/test/199-2-eng-GB/apple.png").
     */
    protected string $uri;

    /**
     * When the variation was last modified.
     */
    protected DateTimeInterface $lastModified;
}
