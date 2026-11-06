<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Image;

use Exception;
use Ibexa\Contracts\Core\Repository\Strategy\ContentThumbnail\Field\FieldTypeBasedThumbnailStrategy;
use Ibexa\Contracts\Core\Repository\Strategy\ContentThumbnail\ThumbnailStrategy;
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

    private ThumbnailStrategy $contentThumbnailStrategy;

    public function __construct(
        ImageThumbnailStrategy $imageThumbnailStrategy,
        ProxyGeneratorInterface $proxyGenerator,
        ThumbnailStrategy $contentThumbnailStrategy
    ) {
        $this->imageThumbnailStrategy = $imageThumbnailStrategy;
        $this->proxyGenerator = $proxyGenerator;
        $this->contentThumbnailStrategy = $contentThumbnailStrategy;
    }

    public function getFieldTypeIdentifier(): string
    {
        return $this->imageThumbnailStrategy->getFieldTypeIdentifier();
    }

    public function getThumbnail(
        Field $field,
        ?VersionInfo $versionInfo = null
    ): Thumbnail {
        $initializer = function (
            &$wrappedObject,
            LazyLoadingInterface $proxy,
            $method,
            array $parameters,
            &$initializer
        ) use ($field, $versionInfo): bool {
            $initializer = null;

            $wrappedObject = $this->imageThumbnailStrategy->getThumbnail($field, $versionInfo);
            if ($wrappedObject !== null) {
                return true;
            }

            // The image strategy has already logged why it failed.
            try {
                $wrappedObject = $this->getFallbackThumbnail($versionInfo);
                $previous = null;
            } catch (Exception $e) {
                $previous = $e;
            }

            if ($wrappedObject === null) {
                throw new RuntimeException(
                    sprintf(
                        'Failed to prepare thumbnail for field "%s" (field type: "%s") using "%s" strategy.',
                        $field->getId(),
                        $field->getFieldTypeIdentifier(),
                        get_debug_type($this->imageThumbnailStrategy),
                    ),
                    0,
                    $previous
                );
            }

            return true;
        };

        return $this->proxyGenerator->createProxy(Thumbnail::class, $initializer);
    }

    /**
     * The proxy is returned before the image variation is generated, so content thumbnail strategies with
     * a lower priority never get a chance to provide one. Ask them directly, without fields, when it fails.
     */
    private function getFallbackThumbnail(?VersionInfo $versionInfo): ?Thumbnail
    {
        if ($versionInfo === null) {
            return null;
        }

        return $this->contentThumbnailStrategy->getThumbnail(
            $versionInfo->getContentInfo()->getContentType(),
            [],
            $versionInfo
        );
    }
}

class_alias(ImageThumbnailProxyStrategy::class, 'eZ\Publish\Core\FieldType\Image\ImageThumbnailProxyStrategy');
