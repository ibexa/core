<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\IO\FilePathNormalizer;

use Ibexa\Core\IO\FilePathNormalizerInterface;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter;
use League\Flysystem\Util;

final class Flysystem implements FilePathNormalizerInterface
{
    private const HASH_PATTERN = '/^[0-9a-f]{12}-/';

    /** @var \Ibexa\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter */
    private $slugConverter;

    public function __construct(SlugConverter $slugConverter)
    {
        $this->slugConverter = $slugConverter;
    }

    public function normalizePath(string $filePath): string
    {
        $fileName = pathinfo($filePath, PATHINFO_BASENAME);
        $directory = pathinfo($filePath, PATHINFO_DIRNAME);

        $fileName = $this->slugConverter->convert($fileName);

        $hash = preg_match(self::HASH_PATTERN, $fileName) ? '' : bin2hex(random_bytes(6)) . '-';

        $filePath = $directory . \DIRECTORY_SEPARATOR . $hash . $fileName;

        return Util::normalizePath($filePath);
    }
}

class_alias(Flysystem::class, 'eZ\Publish\Core\IO\FilePathNormalizer\Flysystem');
