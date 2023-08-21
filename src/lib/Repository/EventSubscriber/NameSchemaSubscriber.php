<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\EventSubscriber;

use Ibexa\Contracts\Core\Event\NameSchema\AbstractNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\AbstractSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveContentNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveNameSchemaEvent;
use Ibexa\Contracts\Core\Event\NameSchema\ResolveUrlAliasSchemaEvent;
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
        $this->processNameSchemaEvent($event);
    }

    public function onResolveContentNameSchema(ResolveContentNameSchemaEvent $event): void
    {
        $this->processNameSchemaEvent($event);
    }

    public function onResolveUrlAliasSchema(ResolveUrlAliasSchemaEvent $event): void
    {
        if (!$this->isValid($event)) {
            return;
        }

        $content = $event->getContent();
        $contentType = $content->getContentType();
        $tokenValues = $this->processEvent(
            array_map(
                static function (Language $language) {
                    return $language->getLanguageCode();
                },
                $event->getContent()->getVersionInfo()->getLanguages()
            ),
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

    private function processNameSchemaEvent(AbstractNameSchemaEvent $event): void
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

    /**
     * @param array<string> $languages
     * @param array<string<array<string>> $identifiers
     * @param array<string<array<string>> $tokenValues
     */
    private function processEvent(
        array $languages,
        array $identifiers,
        ContentType $contentType,
        ?Content $content,
        array $tokenValues,
        ?array $fieldMap = null
    ): array {
        foreach ($languages as $languageCode) {
            $tokenValues[$languageCode] = [];
            foreach ($identifiers as $identifier) {
                $fieldDefinition = $contentType->getFieldDefinition($identifier);
                if (null === $fieldDefinition) {
                    continue;
                }
                $persistenceFieldType = $this->fieldTypeRegistry->getFieldType($fieldDefinition->fieldTypeIdentifier);

                $fieldValue = $fieldMap
                    ? $fieldMap[$identifier][$languageCode] ?? ''
                    : $content->getFieldValue($identifier, $languageCode);

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
