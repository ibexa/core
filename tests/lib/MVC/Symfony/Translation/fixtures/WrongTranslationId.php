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
use Ibexa\Tests\Core\MVC\Symfony\Translation\ExceptionMessageTemplateFileVisitorTest;

/**
 * Broken code stub for ExceptionMessageTemplateFileVisitorTest.
 *
 * @see ExceptionMessageTemplateFileVisitorTest
 */
final class WrongTranslationId extends APIInvalidArgumentException implements Translatable
{
    use TranslatableBase;

    public function __construct(?Exception $previous = null)
    {
        // purposely broken code
        /** @phpstan-ignore argument.type */
        $this->setMessageTemplate(['foo']);

        parent::__construct($this->getBaseTranslation(), 0, $previous);
    }
}
