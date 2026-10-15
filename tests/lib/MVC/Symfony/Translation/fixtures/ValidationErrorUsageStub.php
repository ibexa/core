<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation\fixtures;

use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Tests\Core\MVC\Symfony\Translation\ValidationErrorFileVisitorTest;

/**
 * @see ValidationErrorFileVisitorTest
 */
final class ValidationErrorUsageStub
{
    /**
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function getErrors(): iterable
    {
        yield new ValidationError('error_1.singular_only');

        yield new ValidationError('error_2.singular', 'error_2.plural');
    }
}
