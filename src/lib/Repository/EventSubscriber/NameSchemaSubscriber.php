<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\EventSubscriber;

use Ibexa\Contracts\Core\Event\NameSchema\AbstractNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveContentNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveUrlAliasSchemaEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
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
            ResolveNameSchemaEvent::class => [
                ['onResolveNameSchema', -100],
            ],
            ResolveContentNameSchemaEvent::class => [
                ['onResolveContentNameSchema', -100],
            ],
            ResolveUrlAliasSchemaEvent::class => [
                ['onResolveUrlAliasSchema', -100],
            ],
        ];
    }

    public function onResolveNameSchema(ResolveNameSchemaEvent $event): void
    {
        if (!$this->isValid($event)) {
            return;
        }

        $identifiers = $event->getSchemaIdentifiers()['field'];
        $tokenValues = $event->getTokenValues();
        $fieldMap = $event->getFieldMap();

        $contentType = $event->getContentType();
        foreach ($event->getLanguageCodes() as $languageCode) {
            foreach ($identifiers as $identifier) {
                $fieldDefinition = $contentType->getFieldDefinition($identifier);
                if (null === $fieldDefinition) {
                    continue;
                }
                $persistenceFieldType = $this->fieldTypeRegistry->getFieldType($fieldDefinition->fieldTypeIdentifier);

                $fieldValue = $fieldMap[$identifier][$languageCode] ?? '';
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

    public function onResolveContentNameSchema(ResolveContentNameSchemaEvent $event): void
    {
        if (!$this->isValid($event)) {
            return;
        }

        $content = $event->getContent();
        $contentType = $content->getContentType();
        $tokenValues = $this->processEvent(
            $event->getContent()->getVersionInfo()->getLanguages(),
            $event->getSchemaIdentifiers()['field'],
            $contentType,
            $content,
            $event->getTokenValues()
        );

        $event->setTokenValues($tokenValues);
    }

    public function onResolveUrlAliasSchema(ResolveUrlAliasSchemaEvent $event): void
    {
        if (!$this->isValid($event)) {
            return;
        }

        $content = $event->getContent();
        $contentType = $content->getContentType();
        $tokenValues = $this->processEvent(
            $event->getContent()->getVersionInfo()->getLanguages(),
            $event->getSchemaIdentifiers()['field'],
            $contentType,
            $content,
            $event->getTokenValues()
        );

        $event->setTokenValues($tokenValues);
    }

    public function isValid(AbstractNameSchemaEvent $event): bool
    {
        return array_key_exists('field', $event->getSchemaIdentifiers());
    }

    /**
     * @param array<string, string> $tokens
     * @param array<string> $languageCodes
     * @param array<int, mixed> $attributes
     * @param array<string, mixed> $tokenValues
     *
     * @return array
     */
    public function processEvent(
        $languages,
        $identifiers,
        ContentType $contentType,
        Content $content,
        array $tokenValues
    ): array {
        foreach ($languages as $language) {
            $languageCode = $language->getLanguageCode();
            foreach ($identifiers as $identifier) {
                $fieldDefinition = $contentType->getFieldDefinition($identifier);
                if (null === $fieldDefinition) {
                    continue;
                }
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

        return $tokenValues;
    }
}
