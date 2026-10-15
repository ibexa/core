<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation\fixtures;

use Ibexa\Core\Base\Exceptions\ForbiddenException;
use Ibexa\Tests\Core\MVC\Symfony\Translation\TranslatableExceptionsFileVisitorTest;

/**
 * @see TranslatableExceptionsFileVisitorTest
 */
final class ForbiddenExceptionUsageStub
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     */
    public function foo(): void
    {
        throw new ForbiddenException('Forbidden exception');
    }
}
