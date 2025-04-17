<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * @internal
 */
abstract class BaseStringMapper extends FieldValueMapper
{
    public const string REPLACE_WITH_SPACE_PATTERN = '([\x09\x0B\x0C]+)';
    public const string REMOVE_PATTERN = '([\x00-\x08\x0E-\x1F]+)';

    /**
     * Convert to a proper search engine representation.
     */
    protected function convert(mixed $value): string
    {
        // Replace tab, vertical tab, form-feed chars to single space.
        $value = preg_replace(
            self::REPLACE_WITH_SPACE_PATTERN,
            ' ',
            (string)$value
        );

        // Remove non-printable characters.
        return (string)preg_replace(
            self::REMOVE_PATTERN,
            '',
            (string)$value
        );
    }
}
