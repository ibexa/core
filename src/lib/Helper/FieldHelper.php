<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Helper;

use Ibexa\Contracts\Core\Repository\FieldTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;

class FieldHelper
{
    /** @var \Ibexa\Contracts\Core\Repository\FieldTypeService */
    private $fieldTypeService;

    /** @var TranslationHelper */
    private $translationHelper;

    public function __construct(TranslationHelper $translationHelper, FieldTypeService $fieldTypeService)
    {
        $this->fieldTypeService = $fieldTypeService;
        $this->translationHelper = $translationHelper;
    }

    /**
     * Checks if provided field can be considered empty.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     * @param string $fieldDefIdentifier
     * @param string|null $forcedLanguage
     *
     * @return bool
     */
    public function isFieldEmpty(Content $content, $fieldDefIdentifier, $forcedLanguage = null)
    {
        $field = $this->translationHelper->getTranslatedField($content, $fieldDefIdentifier, $forcedLanguage);
        $fieldDefinition = $content->getContentType()->getFieldDefinition($fieldDefIdentifier);

        return $this
            ->fieldTypeService
            ->getFieldType($fieldDefinition->fieldTypeIdentifier)
            ->isEmptyValue($field->value);
    }
}
