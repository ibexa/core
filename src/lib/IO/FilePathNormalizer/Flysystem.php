<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\FilePathNormalizer;

use Ibexa\Core\IO\FilePathNormalizerInterface;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter;
use League\Flysystem\PathNormalizer;

final class Flysystem implements FilePathNormalizerInterface
{
    private const string HASH_PATTERN = '/^[0-9a-f]{12}-/';

    private SlugConverter $slugConverter;

    private PathNormalizer $pathNormalizer;

    public function __construct(SlugConverter $slugConverter, PathNormalizer $pathNormalizer)
    {
        $this->slugConverter = $slugConverter;
        $this->pathNormalizer = $pathNormalizer;
    }

    public function normalizePath(string $filePath, bool $doHash = true): string
    {
        $fileName = pathinfo($filePath, PATHINFO_BASENAME);
        $directory = pathinfo($filePath, PATHINFO_DIRNAME);

        $fileName = $this->slugConverter->convert($fileName, '_1', 'urlalias');

        $hash = $doHash
            ? (preg_match(self::HASH_PATTERN, $fileName) ? '' : bin2hex(random_bytes(6)) . '-')
            : '';

        $filePath = $directory . \DIRECTORY_SEPARATOR . $hash;
        $normalizedFileName = $this->pathNormalizer->normalizePath($fileName);

        return $filePath . $normalizedFileName;
    }
}
