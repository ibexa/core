<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\EventSubscriber;

use Ibexa\Contracts\Core\Event\ResolveUrlAliasSchemaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NameSchemaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ResolveUrlAliasSchemaEvent::class => [
                ['onResolveUrlAliasSchema', -100],
            ],
        ];
    }

    public function onResolveUrlAliasSchema(ResolveUrlAliasSchemaEvent $event): void
    {
        if (!array_key_exists('field', $event->getSchemaIdentifiers())) {
            return;
        }

        $content = $event->getContent();
        $identifiers = $event->getSchemaIdentifiers()['field'];
        $languages = $event->getContent()->getVersionInfo()->getLanguages();
        $tokenValues = $event->getTokenValues();
        foreach ($languages as $language) {
            $languageCode = $language->getLanguageCode();
            foreach ($identifiers as $identifier) {
                $fieldValue = $content->getFieldValue(
                    $identifier,
                    $languageCode
                );
                $tokenValues[$languageCode][$identifier] = null !== $fieldValue ? (string)$fieldValue : $identifier;
            }
        }

        $event->setTokenValues($tokenValues);
    }
}
