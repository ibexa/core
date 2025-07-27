<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Translation;

use const DIRECTORY_SEPARATOR;

/**
 * Retrieves all installed ibexa translation files ie those installed as ibexa/i18n package.
 */
class GlobCollector implements Collector
{
    /** @var string */
    private $tranlationPattern;

    /**
     * @param string $kernelRootDir
     */
    public function __construct($kernelRootDir)
    {
        $this->tranlationPattern = $kernelRootDir . sprintf('%1$svendor%1$sibexa%1$si18n%1$stranslations%1$s*%1$s*%1$s*.xlf', DIRECTORY_SEPARATOR);
    }

    /**
     * @return array
     */
    public function collect()
    {
        $meta = [];
        foreach (glob($this->tranlationPattern) as $file) {
            [$domain, $locale, $format] = explode('.', basename($file), 3);
            $meta[] = [
                'file' => $file,
                'domain' => $domain,
                'locale' => $locale,
                'format' => $format,
            ];
        }

        return $meta;
    }
}
