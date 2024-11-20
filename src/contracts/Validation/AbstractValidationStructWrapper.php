<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Validation;

/**
 * @template T of object
 */
abstract class AbstractValidationStructWrapper implements ValidationStructWrapperInterface
{
    /**
     * @phpstan-var T
     */
    protected object $struct;

    /**
     * @phpstan-param T $struct
     */
    public function __construct(object $struct)
    {
        $this->struct = $struct;
    }

    /**
     * @phpstan-return T
     */
    final public function getStruct(): object
    {
        return $this->struct;
    }
}
