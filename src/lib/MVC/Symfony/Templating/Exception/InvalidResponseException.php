<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating\Exception;

use Ibexa\Core\Base\Exceptions\ForbiddenException;

class InvalidResponseException extends ForbiddenException
{
    public function __construct(string $whatIsWrong)
    {
        parent::__construct(
            'Response is invalid: %whatIsWrong%',
            [
                '%whatIsWrong%' => $whatIsWrong,
            ]
        );
    }
}
