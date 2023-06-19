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
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

final class NameSchemaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ResolveUrlAliasSchemaEvent::class => 'send',
        ];
    }

    public function send(ResolveUrlAliasSchemaEvent $event): void
    {
        $contentType = $event->getContentType();
        /** @var array $languageCodes */
        $languageCodes = $event->getContent()->versionInfo->languageCodes;

        $names = [];

        $identifiers = $event->getSchemaIdentifiers()['field'] ?? [];
        $inputString = $event->getSchemaName();

        $contentType->fieldDefinitions;

        foreach ($languageCodes as $languageCode) {
            $replacedString = $inputString;
            foreach ($identifiers as $identifier) {
                /** @var \Ibexa\Core\Repository\Values\ContentType\FieldDefinition $fd */
                foreach ($contentType->getFieldDefinitions() as $key => $fd) {
                        $pattern = '/<(\w+)>/'; // Exclude the attribute prefix from the pattern
                        $replacedString = preg_replace_callback($pattern, function ($matches) use ($identifier, $key) {
                            if ($identifier == $key) {
                                $identifier = $matches[1];

                                if (isset($replacementValues[$identifier])) {
                                    return $replacementValues[$identifier];
                                }
                            }

                            return ''; // Return the original token if no replacement value found
                        }, $replacedString);

                        break;
                }
            }

            $names[$languageCode] = $replacedString;
        }

        $event->setNames($names);
    }

    function gggg()
    {


        $inputString = '<x|y>-<name>-<attribute:xxx>';
        $replacementValue = 'Replacement for description';

// Define an array with the identifier and their corresponding replacement values
        $replacementValues = [
            'name' => 'Replacement for name',
            'description' => $replacementValue,
        ];

// Use regular expressions to match and replace the tokens
        $pattern = '/<(\w+)>/'; // Exclude the attribute prefix from the pattern
        $replacedString = preg_replace_callback($pattern, function ($matches) use ($replacementValues) {
            $identifier = $matches[1];

            if (isset($replacementValues[$identifier])) {
                return $replacementValues[$identifier];
            }

            return $matches[0]; // Return the original token if no replacement value found
        }, $inputString);

        echo $replacedString;


    }
}
