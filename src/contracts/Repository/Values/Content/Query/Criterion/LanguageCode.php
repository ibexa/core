<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;

/**
 * A criterion that matches content based on its language code and always-available state.
 *
 * Supported operators:
 * - IN: matches against a list of language codes
 * - EQ: matches against one language code
 */
class LanguageCode extends Criterion implements FilteringCriterion
{
    /**
     * Switch for matching Content that is always-available.
     */
    public bool $matchAlwaysAvailable;

    /**
     * Creates a new LanguageCode criterion.
     *
     * @param string|string[] $value One or more language codes that must be matched
     * @param bool $matchAlwaysAvailable Denotes if always-available Content is to be matched regardless
     *                                      of language codes, this is the default behaviour
     *
     * @throws \InvalidArgumentException if non string value is given
     * @throws \InvalidArgumentException if the value type doesn't match the operator
     */
    public function __construct(string|array $value, bool $matchAlwaysAvailable = true)
    {
        parent::__construct(null, null, $value);

        $this->matchAlwaysAvailable = $matchAlwaysAvailable;
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::IN,
                Specifications::FORMAT_ARRAY,
                Specifications::TYPE_STRING
            ),
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_STRING
            ),
        ];
    }
}
