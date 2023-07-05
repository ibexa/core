<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\EventSubscriber;

use Ibexa\Contracts\Core\Event\ResolveUrlAliasSchemaEvent;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NameSchemaSubscriber implements EventSubscriberInterface
{
    private FieldTypeRegistry $fieldTypeRegistry;

    public function __construct(FieldTypeRegistry $fieldTypeRegistry)
    {
        $this->fieldTypeRegistry = $fieldTypeRegistry;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResolveUrlAliasSchemaEvent::class => [
                ['onResolveUrlAliasSchema', -100],
            ],
        ];
    }

    /**
     * Resolves the URL alias schema by setting token values for specified field identifiers and languages.
     *
     * @param \Ibexa\Contracts\Core\Event\ResolveUrlAliasSchemaEvent $event The event object containing the schema identifiers, content, languages, and token values.
     */
    public function onResolveUrlAliasSchema(ResolveUrlAliasSchemaEvent $event): void
    {
        if (!array_key_exists('field', $event->getSchemaIdentifiers())) {
            return;
        }

        $content = $event->getContent();
        $identifiers = $event->getSchemaIdentifiers()['field'];
        $languages = $event->getContent()->getVersionInfo()->getLanguages();
        $tokenValues = $event->getTokenValues();

        $contentType = $content->getContentType();
        foreach ($languages as $language) {
            $languageCode = $language->getLanguageCode();
            foreach ($identifiers as $identifier) {
                $fieldDefinition = $contentType->getFieldDefinition($identifier);
                $persistenceFieldType = $this->fieldTypeRegistry->getFieldType($fieldDefinition->fieldTypeIdentifier);

                $fieldValue = $content->getFieldValue($identifier, $languageCode);
                $fieldValue = $persistenceFieldType->getName(
                    $fieldValue,
                    $fieldDefinition,
                    $languageCode
                );

                $tokenValues[$languageCode][$identifier] = $fieldValue;
            }
        }

        $event->setTokenValues($tokenValues);
    }
}
