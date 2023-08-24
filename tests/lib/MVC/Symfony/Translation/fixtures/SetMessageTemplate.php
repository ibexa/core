<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation\fixtures;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException as APIInvalidArgumentException;
use Ibexa\Core\Base\Translatable;
use Ibexa\Core\Base\TranslatableBase;

final class SetMessageTemplate extends APIInvalidArgumentException implements Translatable
{
    use TranslatableBase;

    public function __construct(?Exception $previous = null)
    {
        $this->setMessageTemplate('Foo exception');

        parent::__construct($this->getBaseTranslation(), 0, $previous);
    }
}
