<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Repository\SiteAccessAware;

use Ibexa\Contracts\Core\Repository\LanguageService as LanguageServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\LanguageCreateStruct;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

/**
 * LanguageService for SiteAccessAware layer.
 *
 * Hands over calls to aggregated service, except for the default language code,
 * which is resolved from the "languages" setting of the current SiteAccess.
 */
class LanguageService implements LanguageServiceInterface
{
    /** @var LanguageServiceInterface */
    protected $service;

    /** @var ConfigResolverInterface|null */
    protected $configResolver;

    /**
     * Construct service object from aggregated service and ConfigResolver.
     *
     * @param LanguageServiceInterface $service
     * @param ConfigResolverInterface|null $configResolver
     */
    public function __construct(
        LanguageServiceInterface $service,
        ?ConfigResolverInterface $configResolver = null
    ) {
        $this->service = $service;
        $this->configResolver = $configResolver;
    }

    public function createLanguage(LanguageCreateStruct $languageCreateStruct): Language
    {
        return $this->service->createLanguage($languageCreateStruct);
    }

    public function updateLanguageName(
        Language $language,
        string $newName
    ): Language {
        return $this->service->updateLanguageName($language, $newName);
    }

    public function enableLanguage(Language $language): Language
    {
        return $this->service->enableLanguage($language);
    }

    public function disableLanguage(Language $language): Language
    {
        return $this->service->disableLanguage($language);
    }

    public function loadLanguage(string $languageCode): Language
    {
        return $this->service->loadLanguage($languageCode);
    }

    public function loadLanguages(): iterable
    {
        return $this->service->loadLanguages();
    }

    public function loadLanguageById(int $languageId): Language
    {
        return $this->service->loadLanguageById($languageId);
    }

    public function loadLanguageListByCode(array $languageCodes): iterable
    {
        return $this->service->loadLanguageListByCode($languageCodes);
    }

    public function loadLanguageListById(array $languageIds): iterable
    {
        return $this->service->loadLanguageListById($languageIds);
    }

    public function deleteLanguage(Language $language): void
    {
        $this->service->deleteLanguage($language);
    }

    public function getDefaultLanguageCode(): string
    {
        if ($this->configResolver !== null && $this->configResolver->hasParameter('languages')) {
            $languageCodes = $this->configResolver->getParameter('languages');
            if (is_array($languageCodes) && !empty($languageCodes)) {
                return (string)reset($languageCodes);
            }
        }

        return $this->service->getDefaultLanguageCode();
    }

    public function newLanguageCreateStruct(): LanguageCreateStruct
    {
        return $this->service->newLanguageCreateStruct();
    }
}

class_alias(LanguageService::class, 'eZ\Publish\Core\Repository\SiteAccessAware\LanguageService');
