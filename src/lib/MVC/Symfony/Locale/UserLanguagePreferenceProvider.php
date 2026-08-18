<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Locale;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\UserPreferenceService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class UserLanguagePreferenceProvider implements UserLanguagePreferenceProviderInterface
{
    /**
     * @param array<string, list<string>> $languageCodesMap
     */
    public function __construct(
        private RequestStack $requestStack,
        private UserPreferenceService $userPreferenceService,
        private array $languageCodesMap,
        private string $localeFallback
    ) {
    }

    public function getPreferredLocales(?Request $request = null): array
    {
        $languages = [$this->localeFallback];

        $request = $request ?? $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            // `Accept-Language: *` (RFC 7231 wildcard, "any language") is not a concrete
            // locale; exclude it so it never reaches locale/translator handling downstream.
            $browserLanguages = array_values(array_filter(
                $request->getLanguages(),
                static fn (string $language): bool => '*' !== $language
            ));
            if ([] !== $browserLanguages) {
                $languages = $browserLanguages;
            }
        }

        try {
            $preferredLanguage = $this->userPreferenceService->getUserPreference('language')->value;
            array_unshift($languages, $preferredLanguage);
        } catch (NotFoundException) {
        }

        return array_unique($languages);
    }

    public function getPreferredLanguages(): array
    {
        $languageCodes = [[]];
        foreach ($this->getPreferredLocales() as $locale) {
            $locale = strtolower($locale);
            if (!isset($this->languageCodesMap[$locale])) {
                continue;
            }

            $languageCodes[] = $this->languageCodesMap[$locale];
        }

        return array_unique(array_merge(...$languageCodes));
    }
}
