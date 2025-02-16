<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Validation;

use Symfony\Component\Validator\Constraints as Assert;

interface ValidationStructWrapperInterface
{
    #[Assert\Valid]
    public function getStruct(): object;
}
