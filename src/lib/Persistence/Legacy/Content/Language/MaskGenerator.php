<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Language;

use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Core\Base\Exceptions\NotFoundException;

/**
 * Language MaskGenerator.
 */
class MaskGenerator
{
    /**
     * Language lookup.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Language\Handler
     */
    protected $languageHandler;

    /**
     * Creates a new Language MaskGenerator.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Language\Handler $languageHandler
     */
    public function __construct(LanguageHandler $languageHandler)
    {
        $this->languageHandler = $languageHandler;
    }

    /**
     * Generates a language mask from pre-loaded Language Ids.
     *
     * @param int[] $languageIds
     * @param bool $alwaysAvailable
     *
     * @return int
     */
    public function generateLanguageMaskFromLanguageIds(array $languageIds, $alwaysAvailable): int
    {
        // make sure alwaysAvailable part of bit mask always results in 1 or 0
        $languageMask = $alwaysAvailable ? 1 : 0;

        foreach ($languageIds as $languageId) {
            $languageMask |= $languageId;
        }

        return $languageMask;
    }

    /**
     * Generates a language indicator from $languageCode and $alwaysAvailable.
     *
     * @param string $languageCode
     * @param bool $alwaysAvailable
     *
     * @return int
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function generateLanguageIndicator($languageCode, $alwaysAvailable): int
    {
        return $this->languageHandler->loadByLanguageCode($languageCode)->id | ($alwaysAvailable ? 1 : 0);
    }

    /**
     * Checks if $language is always available in $languages;.
     *
     * @param string $language
     * @param array $languages
     *
     * @return bool
     */
    public function isLanguageAlwaysAvailable($language, array $languages): bool
    {
        return isset($languages['always-available'])
           && ($languages['always-available'] == $language)
        ;
    }

    /**
     * Checks if $languageMask contains the alwaysAvailable bit field.
     *
     * @param int $languageMask
     *
     * @return bool
     */
    public function isAlwaysAvailable($languageMask): bool
    {
        return (bool)($languageMask & 1);
    }

    /**
     * Removes the alwaysAvailable flag from $languageId and returns cleaned up $languageId.
     *
     * @param int $languageId
     *
     * @return int
     */
    public function removeAlwaysAvailableFlag($languageId): int
    {
        return $languageId & ~1;
    }

    /**
     * Extracts every language Ids contained in $languageMask.
     *
     * @param int $languageMask
     *
     * @return array Array of language Id
     */
    public function extractLanguageIdsFromMask($languageMask): array
    {
        $exp = 2;
        $result = [];

        // Decomposition of $languageMask into its binary components.
        // check if $exp has not overflown and became float (happens for the last possible language in the mask)
        while (is_int($exp) && $exp <= $languageMask) {
            if ($languageMask & $exp) {
                $result[] = $exp;
            }

            $exp *= 2;
        }

        return $result;
    }

    /**
     * Extracts Language codes contained in given $languageMask.
     *
     * @param int $languageMask
     *
     * @return array
     */
    public function extractLanguageCodesFromMask($languageMask): array
    {
        $languageCodes = [];
        $languageList = $this->languageHandler->loadList(
            $this->extractLanguageIdsFromMask($languageMask)
        );
        foreach ($languageList as $language) {
            $languageCodes[] = $language->languageCode;
        }

        return $languageCodes;
    }

    /**
     * Checks if given $languageMask consists of multiple languages.
     *
     * @param int $languageMask
     *
     * @return bool
     */
    public function isLanguageMaskComposite($languageMask): bool
    {
        // Ignore first bit
        $languageMask = $this->removeAlwaysAvailableFlag($languageMask);

        // Special case
        if ($languageMask === 0) {
            return false;
        }

        // Return false if power of 2
        return (bool)($languageMask & ($languageMask - 1));
    }

    /**
     * Generates a language mask from plain array of language codes and always available flag.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If language(s) in $languageCodes was not be found
     *
     * @param string[] $languageCodes
     * @param bool $isAlwaysAvailable
     *
     * @return int
     */
    public function generateLanguageMaskFromLanguageCodes(array $languageCodes, bool $isAlwaysAvailable = false): int
    {
        $mask = $isAlwaysAvailable ? 1 : 0;

        $languageList = $this->languageHandler->loadListByLanguageCodes($languageCodes);
        foreach ($languageList as $language) {
            $mask |= $language->id;
        }

        if ($missing = array_diff($languageCodes, array_keys($languageList))) {
            throw new NotFoundException('Language', implode(', ', $missing));
        }

        return $mask;
    }

    /**
     * Collect all translations of the given Persistence Fields and generate language mask.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field[] $fields
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function generateLanguageMaskForFields(
        array $fields,
        string $initialLanguageCode,
        bool $isAlwaysAvailable
    ): int {
        $languages = [$initialLanguageCode => true];
        foreach ($fields as $field) {
            if (isset($languages[$field->languageCode])) {
                continue;
            }

            $languages[$field->languageCode] = true;
        }

        return $this->generateLanguageMaskFromLanguageCodes(
            array_keys($languages),
            $isAlwaysAvailable
        );
    }
}
