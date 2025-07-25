<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Media;

use Ibexa\Core\FieldType\BinaryBase\Value as BaseValue;

/**
 * Value for the Media field type.
 */
class Value extends BaseValue
{
    /**
     * If the media has a controller when being displayed.
     */
    public bool $hasController = false;

    /**
     * If the media should be automatically played.
     */
    public bool $autoplay = false;

    /**
     * If the media should be played in a loop.
     */
    public bool $loop = false;

    /**
     * Height of the media.
     */
    public int $height = 0;

    /**
     * Width of the media.
     */
    public int $width = 0;
}
