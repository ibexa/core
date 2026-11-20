<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Limitation;

use Ibexa\Contracts\Core\Persistence\Handler as SPIPersistenceHandler;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;

/**
 * LocationLimitation is a Content limitation.
 */
class AbstractPersistenceLimitationType
{
    /** @var SPIPersistenceHandler */
    protected $persistence;

    /**
     * @param SPIPersistenceHandler $persistence
     */
    public function __construct(SPIPersistenceHandler $persistence)
    {
        $this->persistence = $persistence;
    }

    /**
     * Limitation values come back from storage as int, but may be given as digits-only strings.
     *
     * @param mixed $id
     *
     * @throws InvalidArgumentType if $id is neither an int nor a digits-only string
     */
    protected function normalizeIntId(
        $id,
        string $key,
        bool $allowNegative = false
    ): int {
        if (is_int($id)) {
            return $id;
        }

        // Dollar-endonly (D), so "12\n" is rejected the way ctype_digit() rejects it
        $pattern = $allowNegative ? '/^-?\d+$/D' : '/^\d+$/D';
        if (is_string($id) && preg_match($pattern, $id) === 1) {
            return (int)$id;
        }

        throw new InvalidArgumentType("\$limitationValue->limitationValues[{$key}]", 'int', $id);
    }
}

class_alias(AbstractPersistenceLimitationType::class, 'eZ\Publish\Core\Limitation\AbstractPersistenceLimitationType');
