<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\Imagine;

use Ibexa\Contracts\Core\Variation\VariationPathGenerator as VariationPathGeneratorContract;

/**
 * @deprecated 4.4.0 Use \Ibexa\Contracts\Core\Variation\VariationPathGenerator instead.
 */
interface VariationPathGenerator extends VariationPathGeneratorContract
{
}

class_alias(VariationPathGenerator::class, 'eZ\Bundle\EzPublishCoreBundle\Imagine\VariationPathGenerator');
