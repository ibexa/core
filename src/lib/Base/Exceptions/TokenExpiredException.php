<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Base\Exceptions;

use DateTimeInterface;
use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException as APIUnauthorizedException;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;

final class TokenExpiredException extends APIUnauthorizedException implements Httpable, Translatable
{
    use TranslatableBase;

    public function __construct(
        string $tokenType,
        string $token,
        DateTimeInterface $when,
        ?Exception $previous = null
    ) {
        $this->setMessageTemplate("Token '%tokenType%:%token%' expired on '%when%'");
        $this->setParameters([
            '%tokenType%' => $tokenType,
            '%token%' => $token,
            '%when%' => $when->format(DateTimeInterface::ATOM),
        ]);

        parent::__construct($this->getBaseTranslation(), self::UNAUTHORIZED, $previous);
    }
}
