<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Translation\Annotation;

use JMS\TranslationBundle\Exception\RuntimeException;

/**
 * @Annotation
 */
final class Domain
{
    /** @var string @Required */
    public $value;

    public function __construct()
    {
        if (0 === func_num_args()) {
            return;
        }

        $values = func_get_arg(0);

        if (!isset($values['value'])) {
            throw new RuntimeException('The "value" attribute for annotation "@Domain" must be set.');
        }

        $this->value = $values['value'];
    }
}
