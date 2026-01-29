<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Image;

use Ibexa\Contracts\Core\Repository\Strategy\ContentThumbnail\Field\FieldTypeBasedThumbnailStrategy;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Thumbnail;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\ProxyFactory\ProxyGeneratorInterface;
use ProxyManager\Proxy\LazyLoadingInterface;
use RuntimeException;

final class ImageThumbnailProxyStrategy implements FieldTypeBasedThumbnailStrategy
{
    private ImageThumbnailStrategy $imageThumbnailStrategy;

    private ProxyGeneratorInterface $proxyGenerator;

    public function __construct(
        ImageThumbnailStrategy $imageThumbnailStrategy,
        ProxyGeneratorInterface $proxyGenerator
    ) {
        $this->imageThumbnailStrategy = $imageThumbnailStrategy;
        $this->proxyGenerator = $proxyGenerator;
    }

    public function getFieldTypeIdentifier(): string
    {
        return $this->imageThumbnailStrategy->getFieldTypeIdentifier();
    }

    public function getThumbnail(Field $field, ?VersionInfo $versionInfo = null): Thumbnail
    {
        $initializer = function (
            &$wrappedObject,
            LazyLoadingInterface $proxy,
            $method,
            array $parameters,
            &$initializer
        ) use ($field, $versionInfo): bool {
            $initializer = null;

            $wrappedObject = $this->imageThumbnailStrategy->getThumbnail($field, $versionInfo);

            if ($wrappedObject === null) {
                throw new RuntimeException(sprintf(
                    'Failed to prepare thumbnail for field type "%s" (ID: %s) using "%s" strategy.',
                    $field->getId(),
                    $field->getFieldTypeIdentifier(),
                    get_debug_type($this->imageThumbnailStrategy),
                ));
            }

            return true;
        };

        return $this->proxyGenerator->createProxy(Thumbnail::class, $initializer);
    }
}
