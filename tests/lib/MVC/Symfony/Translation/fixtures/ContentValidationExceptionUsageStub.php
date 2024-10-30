<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation\fixtures;

use Ibexa\Core\Base\Exceptions\ContentValidationException;

/**
 * @see \Ibexa\Tests\Core\MVC\Symfony\Translation\TranslatableExceptionsFileVisitorTest
 */
final class ContentValidationExceptionUsageStub
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     */
    public function foo(): void
    {
        throw new ContentValidationException(
            'Content with ID %contentId% could not be found',
            ['%contentId%' => 123]
        );
    }
}
