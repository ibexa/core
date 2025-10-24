<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\Exception;

use Ibexa\Core\Base\Exceptions\NotFoundException as BaseNotFoundException;
use Throwable;

class BinaryFileNotFoundException extends BaseNotFoundException
{
    public function __construct(
        string $path,
        ?Throwable $previous = null
    ) {
        parent::__construct('BinaryFile', $path, $previous);
    }
}
