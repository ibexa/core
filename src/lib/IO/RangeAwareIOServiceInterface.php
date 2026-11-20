<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO;

use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\IO\Exception\IOException;
use Ibexa\Core\IO\Values\BinaryFile;

interface RangeAwareIOServiceInterface extends IOServiceInterface
{
    /**
     * The resource MAY contain more than $length bytes; consumers must limit reading on their own.
     *
     * @param int|null $length null reads until the end of the file
     *
     * @return resource positioned at $offset
     *
     * @throws InvalidArgumentValue
     * @throws IOException
     */
    public function getFileInputStreamRange(
        BinaryFile $binaryFile,
        int $offset,
        ?int $length = null
    );
}
