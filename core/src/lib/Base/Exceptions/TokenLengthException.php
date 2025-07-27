<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use Exception;
use Ibexa\Contracts\Core\Repository\Values\Token\Token;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException as ApiInvalidArgumentException;

final class TokenLengthException extends ApiInvalidArgumentException
{
    public function __construct(
        int $tokenLength,
        int $maxTokenLength = Token::MAX_LENGTH,
        ?Exception $previous = null
    ) {
        parent::__construct(
            'tokenLength',
            sprintf(
                'Token length is too long: %d characters. Max length is %d.',
                $tokenLength,
                $maxTokenLength
            ),
            $previous
        );
    }
}
