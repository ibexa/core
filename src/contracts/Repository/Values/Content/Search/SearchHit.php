<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a SearchHit matching the query.
 *
 * @template-covariant T of \Ibexa\Contracts\Core\Repository\Values\ValueObject
 */
class SearchHit extends ValueObject
{
    /**
     * The value found by the search.
     *
     * @phpstan-var T
     */
    public $valueObject;

    /**
     * The score of this value;.
     */
    public ?float $score = null;

    /**
     * The index identifier where this value was found.
     */
    public ?string $index = null;

    /**
     * Language code of the Content translation that matched the query.
     */
    public string $matchedTranslation;

    /**
     * A representation of the search hit including highlighted terms.
     */
    public ?string $highlight = null;
}
