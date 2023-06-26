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
            ResolveUrlAliasSchemaEvent::class => [
                ['onResolveUrlAliasSchema', -100],
            ],
        ];
    }


    public function onResolveUrlAliasSchema(ResolveUrlAliasSchemaEvent $event): void
    {
        $inputString = $event->getSchemaName();
        $content = $event->getContent();
        $languageCodes = $event->getContent()->versionInfo->languageCodes;

        $names = $event->getNames();

        foreach ($languageCodes as $languageCode) {
            $pattern = '/<(\w+)>/';
            $stringToReplace = $names[$languageCode] ?? $inputString;
            $stringToReplace = preg_replace_callback($pattern, function ($matches) use ($content, $languageCode) {
                $fieldIdentifier = $matches[1];

                return $content->getFieldValue($fieldIdentifier, $languageCode);
            }, $stringToReplace);
            $names[$languageCode] = $stringToReplace;
        }

        $event->setNames($names);
    }
}
