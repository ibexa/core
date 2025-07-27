<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Exception;

use Exception;
use Ibexa\Core\Base\Exceptions\NotFoundException as BaseNotFoundException;

class BinaryFileNotFoundException extends BaseNotFoundException
{
    public function __construct(string $path, ?Exception $previous = null)
    {
        parent::__construct('BinaryFile', $path, $previous);
    }
}
