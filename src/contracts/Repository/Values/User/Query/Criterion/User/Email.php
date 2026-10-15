<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Query\Criterion\User;

use Ibexa\Contracts\Core\Repository\Values\User\Query\UserCriterionInterface;

/**
 * Matches users by their exact email address(es).
 */
final readonly class Email implements UserCriterionInterface
{
    /**
     * @param string|list<string> $value
     */
    public function __construct(
        private string|array $value
    ) {
    }

    /**
     * @return string|list<string>
     */
    public function getValue(): string|array
    {
        return $this->value;
    }
}
