<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\ContentType;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeDraft;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;

final class AddFieldDefinitionEvent extends AfterEvent
{
    private ContentTypeDraft $contentTypeDraft;

    private FieldDefinitionCreateStruct $fieldDefinitionCreateStruct;

    public function __construct(
        ContentTypeDraft $contentTypeDraft,
        FieldDefinitionCreateStruct $fieldDefinitionCreateStruct
    ) {
        $this->contentTypeDraft = $contentTypeDraft;
        $this->fieldDefinitionCreateStruct = $fieldDefinitionCreateStruct;
    }

    public function getContentTypeDraft(): ContentTypeDraft
    {
        return $this->contentTypeDraft;
    }

    public function getFieldDefinitionCreateStruct(): FieldDefinitionCreateStruct
    {
        return $this->fieldDefinitionCreateStruct;
    }
}
