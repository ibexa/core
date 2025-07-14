<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\IO\Values;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * Create struct for BinaryFile objects.
 */
class BinaryFileCreateStruct extends ValueObject
{
    /**
     * URI the binary file should be stored to.
     */
    public ?string $id = null;

    /**
     * The size of the file.
     */
    public int $size;

    /**
     * the input stream.
     *
     * @var resource
     */
    public mixed $inputStream;

    /**
     * The file's mime type.
     *
     * If not provided, will be auto-detected by the IOService
     *
     * Example: text/xml.
     */
    public string $mimeType;
}
