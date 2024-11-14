<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Validation;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @template T of object
 *
 * @implements \Ibexa\Contracts\Core\Validation\ValidatorStructWrapperInterface<T>
 */
abstract class AbstractValidationStructWrapper implements ValidatorStructWrapperInterface
{
    /**
     * @phpstan-var T
     *
     * @Assert\Valid()
     */
    private object $struct;

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

    final public function getStructName(): string
    {
        return 'struct';
    }
}
