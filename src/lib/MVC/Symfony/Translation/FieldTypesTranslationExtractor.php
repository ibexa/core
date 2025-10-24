<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Translation;

use Ibexa\Core\FieldType\FieldTypeRegistry;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\ExtractorInterface;

/**
 * Generates translation strings for fieldtypes names (<FieldTypeIdentifier>.name).
 */
class FieldTypesTranslationExtractor implements ExtractorInterface
{
    /** @var FieldTypeRegistry */
    private $fieldTypeRegistry;

    public function __construct(FieldTypeRegistry $fieldTypeRegistry)
    {
        $this->fieldTypeRegistry = $fieldTypeRegistry;
    }

    public function extract()
    {
        $catalogue = new MessageCatalogue();
        foreach ($this->fieldTypeRegistry->getConcreteFieldTypesIdentifiers() as $fieldTypeIdentifier) {
            $catalogue->add(
                new Message(
                    $fieldTypeIdentifier . '.name',
                    'ibexa_fieldtypes'
                )
            );
        }

        return $catalogue;
    }
}
