<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\ImageAsset;

use Ibexa\Core\FieldType\Value as BaseValue;

class Value extends BaseValue
{
    /**
     * @param int|null $destinationContentId Related content's ID.
     * @param string|null $alternativeText The alternative image text (for example, "Picture of an apple.").
     */
    public function __construct(
        public readonly ?int $destinationContentId = null,
        public readonly ?string $alternativeText = null
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return (string) $this->destinationContentId;
    }
}
