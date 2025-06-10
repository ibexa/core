<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Exception;

use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;

class InvalidBinaryFileIdException extends InvalidArgumentValue
{
    public function __construct(string $identifier)
    {
        parent::__construct('BinaryFile::id', $identifier, 'BinaryFile');
    }
}
