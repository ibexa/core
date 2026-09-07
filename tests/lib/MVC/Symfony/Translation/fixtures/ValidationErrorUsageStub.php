<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\Translation\fixtures;

use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\MVC\Symfony\Translation\Annotation\Domain;
use JMS\TranslationBundle\Annotation\Desc;

/**
 * @see \Ibexa\Tests\Core\MVC\Symfony\Translation\ValidationErrorFileVisitorTest
 */
final class ValidationErrorUsageStub
{
    private const SINGULAR = 'error_2.singular';
    private const PLURAL = 'error_2.plural';
    private const WITH_DESC = 'error_3.with_desc';
    private const WITH_VALIDATORS_DOMAIN = 'error_4.validators_domain';

    /**
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function getErrors(): iterable
    {
        yield new ValidationError('error_1.singular_only');

        yield new ValidationError('error_2.singular', 'error_2.plural');

        yield new ValidationError(static::SINGULAR, self::PLURAL);

        yield new ValidationError(
            /** @Desc("Validation error extracted from class const") */
            self::WITH_DESC
        );

        yield new ValidationError(
            /** @Desc("Validation error extracted into validators domain") @Domain("validators") */
            self::WITH_VALIDATORS_DOMAIN
        );
    }
}
