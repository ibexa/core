<?php

declare(strict_types=1);

namespace Ibexa\Core\Repository\EventSubscriber;

use Ibexa\Contracts\Core\Event\ResolveUrlAliasSchemaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NameSchemaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ResolveUrlAliasSchemaEvent::class => 'onResolveUrlAliasSchema',
        ];
    }

    public function onResolveUrlAliasSchema(ResolveUrlAliasSchemaEvent $event): void
    {
        $inputString = $event->getSchemaName();
        $identifiers = $event->getSchemaIdentifiers()['field'] ?? [];
        $contentType = $event->getContentType();
        $languageCodes = $event->getContent()->versionInfo->languageCodes;

        $names = [];

        foreach ($languageCodes as $languageCode) {
            $replacedString = $inputString;

            foreach ($identifiers as $identifier) {
                $replacedString = $this->replacePlaceholdersInString(
                    $replacedString,
                    $identifier,
                    $contentType
                );
            }

            $names[$languageCode] = $replacedString;
        }

        $event->setNames($names);
    }

    private function replacePlaceholdersInString(
        string $inputString,
        string $identifier,
        $contentType
    ): string {
        $pattern = '/<(\w+)>/';

        return preg_replace_callback($pattern, function ($matches) use ($identifier, $contentType) {
            $fieldIdentifier = $matches[1];

            foreach ($contentType->getFieldDefinitions() as $key => $fieldDefinition) {
                if ($identifier == $key) {
                    if (isset($replacementValues[$fieldIdentifier])) {
                        return $replacementValues[$fieldIdentifier];
                    }
                }
            }

            return ''; // Return the original token if no replacement value found
        }, $inputString);
    }
}
