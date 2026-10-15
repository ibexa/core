<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\EventSubscriber;

use Ibexa\Contracts\Core\Event\NameSchema\AbstractSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveContentNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveUrlAliasSchemaEvent;
use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
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

        $tokenValues = $this->processEvent(
            $event->getLanguageCodes(),
            $event->getSchemaIdentifiers()['field'],
            $event->getContentType(),
            null,
            $event->getTokenValues(),
            $event->getFieldMap()
        );

        $event->setTokenValues($tokenValues);
    }

    public function onResolveContentNameSchema(ResolveContentNameSchemaEvent $event): void
    {
        if (!$this->isValid($event)) {
            return;
        }

        $tokenValues = $this->processEvent(
            $event->getLanguageCodes(),
            $event->getSchemaIdentifiers()['field'],
            $event->getContentType(),
            $event->getContent(),
            $event->getTokenValues(),
            $event->getFieldMap()
        );

        $event->setTokenValues($tokenValues);
    }

    public function onResolveUrlAliasSchema(ResolveUrlAliasSchemaEvent $event): void
    {
        if (!$this->isValid($event)) {
            return;
        }

        $languageList = array_map(
            static fn (Language $language): string => $language->getLanguageCode(),
            (array)$event->getContent()->getVersionInfo()->getLanguages()
        );

        $content = $event->getContent();
        $contentType = $content->getContentType();
        $tokenValues = $this->processEvent(
            $languageList,
            $event->getSchemaIdentifiers()['field'],
            $contentType,
            $content,
            $event->getTokenValues()
        );

        $event->setTokenValues($tokenValues);
    }

    public function isValid(AbstractSchemaEvent $event): bool
    {
        return array_key_exists('field', $event->getSchemaIdentifiers());
    }

    /**
     * @param array<string> $languages
     * @param array<int, string> $identifiers
     * @param array<string, array<string, string>> $tokenValues
     * @param array<int|string, array<string, Value>> $fieldMap
     *
     * @return array<string, array<string, string>>
     */
    private function processEvent(
        array $languages,
        array $identifiers,
        ContentType $contentType,
        ?Content $content,
        array $tokenValues,
        array $fieldMap = []
    ): array {
        foreach ($languages as $languageCode) {
            $values = $content !== null || !empty($fieldMap)
                ? $this->getValues(
                    $identifiers,
                    $contentType,
                    $content,
                    $fieldMap,
                    $languageCode
                )
                : [];
            $tokenValues[$languageCode] = array_merge($tokenValues[$languageCode] ?? [], $values);
        }

        return $tokenValues;
    }

    /**
     * @param array<int, string> $identifiers
     * @param array<int|string, array<string, Value>> $fieldMap
     *
     * @return array<string, string>
     */
    private function getValues(
        array $identifiers,
        ContentType $contentType,
        ?Content $content,
        array $fieldMap,
        string $languageCode
    ): array {
        $tokenValues = [];
        foreach ($identifiers as $identifier) {
            $fieldDefinition = $contentType->getFieldDefinition($identifier);
            if (null === $fieldDefinition) {
                continue;
            }

            $persistenceFieldType = $this->fieldTypeRegistry->getFieldType($fieldDefinition->fieldTypeIdentifier);

            if (!empty($fieldMap)) {
                $fieldValue = $fieldMap[$identifier][$languageCode] ?? null;
            } else {
                $fieldValue = $content !== null ? $content->getFieldValue($identifier, $languageCode) : null;
            }

            $tokenValues[$identifier] = $fieldValue !== null ? $persistenceFieldType->getName(
                $fieldValue,
                $fieldDefinition,
                $languageCode
            ) : '';
        }

        return $tokenValues;
    }
}
