<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Variation\Values;

/**
 * @property-read int|null $width The width as number of pixels (for example "320")
 * @property-read int|null $height The height as number of pixels (for example "256")
 * @property-read string $name The name of the image alias (for example "original")
 * @property-read mixed $info Extra information about the image, depending on the image type
 * @property-read mixed $imageId
 */
class ImageVariation extends Variation
{
    /**
     * The width as number of pixels (for example "320").
     */
    protected ?int $width = null;

    /**
     * The height as number of pixels (for example "256").
     */
    protected ?int $height = null;

    /**
     * The name of the image alias (for example "original").
     */
    protected string $name;

    /**
     * Contains extra information about the image, depending on the image type.
     * It will typically contain EXIF information from digital cameras or information about animated GIFs.
     * If there is no information, the info will be a boolean FALSE.
     *
     * Beware: This information may contain e.g. HTML, JavaScript, or PHP code, and should be treated like any
     * other user-submitted data. Make sure it is properly escaped before use.
     */
    protected mixed $info;

    protected mixed $imageId;

    /**
     * Contains identifier of variation handler used to generate this particular variation.
     */
    protected ?string $handler = null;

    /**
     * Indicator if variation image is external (like Fastly IO) or local (like built-in Imagine based alias).
     * External images won't have SPLInfo data and image dimensions as it would be redundant to fetch file.
     */
    protected bool $isExternal = false;
}
