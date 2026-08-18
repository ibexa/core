<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\UrlAlias;

use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\UrlAlias;

/**
 * UrlAlias Mapper.
 */
class Mapper
{
    private Gateway $gateway;

    private LanguageHandler $languageHandler;

    public function __construct(Gateway $gateway, LanguageHandler $languageHandler)
    {
        $this->gateway = $gateway;
        $this->languageHandler = $languageHandler;
    }

    /**
     * @param int[] $languageIds
     *
     * @return string[]
     */
    private function loadLanguageCodes(array $languageIds): array
    {
        $languageCodes = [];
        foreach ($this->languageHandler->loadList($languageIds) as $language) {
            $languageCodes[] = $language->languageCode;
        }

        return $languageCodes;
    }

    /**
     * Creates a UrlAlias object from database row data.
     *
     * @param mixed[] $data
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\UrlAlias
     */
    public function extractUrlAliasFromData($data)
    {
        $urlAlias = new UrlAlias();

        list($type, $destination) = $this->matchTypeAndDestination($data['action']);
        $urlAlias->id = $this->generateIdentityKey((int)$data['parent'], $data['text_md5']);
        $urlAlias->pathData = $this->normalizePathData($data['raw_path_data']);
        $urlAlias->languageCodes = $this->loadLanguageCodes(
            $this->gateway->loadTranslationLanguageIds((int)$data['parent'], $data['text_md5'])
        );
        $urlAlias->alwaysAvailable = (bool)$data['is_always_available'];
        $urlAlias->isHistory = isset($data['is_path_history']) ? $data['is_path_history'] : !$data['is_original'];
        $urlAlias->isCustom = (bool)$data['is_alias'];
        $urlAlias->forward = $data['is_alias'] && $data['alias_redirects'];
        $urlAlias->destination = $destination;
        $urlAlias->type = $type;

        return $urlAlias;
    }

    /**
     * Extracts UrlAlias objects from database $rows.
     *
     * @param array $rows
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\UrlAlias[]
     */
    public function extractUrlAliasListFromData(array $rows)
    {
        $urlAliases = [];
        foreach ($rows as $row) {
            $urlAliases[] = $this->extractUrlAliasFromData($row);
        }

        return $urlAliases;
    }

    /**
     * Extracts language codes from database $rows.
     *
     * @param array $rows
     *
     * @return string[]
     */
    public function extractLanguageCodesFromData(array $rows): array
    {
        $languageIds = [];
        foreach ($rows as $row) {
            $languageIds[] = $this->gateway->loadTranslationLanguageIds((int)$row['parent'], $row['text_md5']);
        }

        return $this->loadLanguageCodes(array_unique(array_merge([], ...$languageIds)));
    }

    public function generateIdentityKey(int $parentId, string $hash): string
    {
        return sprintf('%d-%s', $parentId, $hash);
    }

    /**
     * @throws \RuntimeException
     *
     * @param string $action
     *
     * @return array
     */
    protected function matchTypeAndDestination($action)
    {
        if (preg_match('#^([a-zA-Z0-9_]+):(.+)?$#', $action, $matches)) {
            $actionType = $matches[1];
            $actionValue = isset($matches[2]) ? $matches[2] : false;

            switch ($actionType) {
                case 'eznode':
                    $type = UrlAlias::LOCATION;
                    $destination = (int)$actionValue;
                    break;

                case 'module':
                    $type = UrlAlias::RESOURCE;
                    $destination = $actionValue;
                    break;

                case 'nop':
                    $type = UrlAlias::VIRTUAL;
                    $destination = null;
                    break;

                default:
                    // @todo log message
                    throw new \RuntimeException("Action type '{$actionType}' is unknown");
            }
        } else {
            // @todo log message
            throw new \RuntimeException("Action '{$action}' is not valid");
        }

        return [$type, $destination];
    }

    /**
     * @param array $pathData
     *
     * @return array
     */
    protected function normalizePathData(array $pathData)
    {
        $normalizedPathData = [];
        foreach ($pathData as $level => $rows) {
            $pathElementData = [];
            foreach ($rows as $row) {
                $this->normalizePathDataRow($pathElementData, $row);
            }

            $normalizedPathData[$level] = $pathElementData;
        }

        return $normalizedPathData;
    }

    /**
     * @param array $pathElementData
     * @param array $row
     */
    protected function normalizePathDataRow(array &$pathElementData, array $row)
    {
        $languageCodes = $this->loadLanguageCodes(
            $this->gateway->loadTranslationLanguageIds((int)$row['parent'], $row['text_md5'])
        );
        $pathElementData['always-available'] = (bool)$row['is_always_available'];
        if (!empty($languageCodes)) {
            foreach ($languageCodes as $languageCode) {
                $pathElementData['translations'][$languageCode] = $row['text'];
            }
        } elseif ($pathElementData['always-available']) {
            // NOP entry, lang_mask == 1
            $pathElementData['translations']['always-available'] = $row['text'];
        }
    }
}
