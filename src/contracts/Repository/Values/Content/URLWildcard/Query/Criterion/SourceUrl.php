<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Matches URLWildcards which contains the source Url.
 */
final class SourceUrl extends Matcher
{
    public string $sourceUrl;

    public function __construct(string $sourceUrl)
    {
        if ($sourceUrl === '') {
            throw new InvalidArgumentException('sourceUrl', 'URLWildcard source url cannot be empty.');
        }

        $this->sourceUrl = $sourceUrl;
    }
}
