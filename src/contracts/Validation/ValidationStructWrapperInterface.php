<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\Validation;

/**
 * @template T of object
 */
interface ValidationStructWrapperInterface
{
    public function getStructName(): string;

    /**
     * @phpstan-return T
     */
    public function getStruct(): object;
}
