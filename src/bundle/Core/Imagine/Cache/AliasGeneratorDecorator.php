<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\Cache;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Variation\Values\Variation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Persistence Cache layer for AliasGenerator.
 */
class AliasGeneratorDecorator implements VariationHandler, SiteAccessAware
{
    private const IMAGE_VARIATION_IDENTIFIER = 'image_variation';
    private const IMAGE_VARIATION_SITEACCESS_IDENTIFIER = 'image_variation_siteaccess';
    private const IMAGE_VARIATION_CONTENT_IDENTIFIER = 'image_variation_content';
    private const IMAGE_VARIATION_FIELD_IDENTIFIER = 'image_variation_field';
    private const IMAGE_VARIATION_NAME_IDENTIFIER = 'image_variation_name';
    private const CONTENT_IDENTIFIER = 'content';
    private const CONTENT_VERSION_IDENTIFIER = 'content_version';

    /** @var VariationHandler */
    private $aliasGenerator;

    /** @var TagAwareAdapterInterface */
    private $cache;

    /** @var SiteAccess */
    private $siteAccess;

    /** @var RequestContext */
    private $requestContext;

    /** @var CacheIdentifierGeneratorInterface */
    private $cacheIdentifierGenerator;

    /**
     * @param VariationHandler $aliasGenerator
     * @param TagAwareAdapterInterface $cache
     * @param RequestContext $requestContext
     * @param CacheIdentifierGeneratorInterface $cacheIdentifierGenerator
     */
    public function __construct(
        VariationHandler $aliasGenerator,
        TagAwareAdapterInterface $cache,
        RequestContext $requestContext,
        CacheIdentifierGeneratorInterface $cacheIdentifierGenerator
    ) {
        $this->aliasGenerator = $aliasGenerator;
        $this->cache = $cache;
        $this->requestContext = $requestContext;
        $this->cacheIdentifierGenerator = $cacheIdentifierGenerator;
    }

    /**
     * @param Field $field
     * @param VersionInfo $versionInfo
     * @param string $variationName
     * @param array $parameters
     *
     * @return Variation
     *
     * @throws InvalidArgumentException&\Throwable
     */
    public function getVariation(
        Field $field,
        VersionInfo $versionInfo,
        string $variationName,
        array $parameters = []
    ): Variation {
        $item = $this->cache->getItem($this->getCacheKey($field, $versionInfo, $variationName));
        $image = $item->get();
        if (!$item->isHit()) {
            $image = $this->aliasGenerator->getVariation($field, $versionInfo, $variationName, $parameters);
            $item->set($image);
            $item->tag($this->getTagsForVariation($field, $versionInfo, $variationName));
            $this->cache->save($item);
        }

        return $image;
    }

    /**
     * @param SiteAccess|null $siteAccess
     */
    public function setSiteAccess(?SiteAccess $siteAccess = null)
    {
        $this->siteAccess = $siteAccess;
    }

    /**
     * @param Field $field
     * @param VersionInfo $versionInfo
     * @param string $variationName
     *
     * @return string
     */
    private function getCacheKey(
        Field $field,
        VersionInfo $versionInfo,
        $variationName
    ): string {
        return sprintf(
            $this->cacheIdentifierGenerator->generateKey(self::IMAGE_VARIATION_IDENTIFIER, [], true) . '-%s-%s-%s-%d-%d-%d-%s-%s',
            $this->siteAccess->name ?? 'default',
            $this->requestContext->getScheme(),
            $this->requestContext->getHost(),
            $this->requestContext->getScheme() === 'https' ? $this->requestContext->getHttpsPort() : $this->requestContext->getHttpPort(),
            $versionInfo->getContentInfo()->id,
            $versionInfo->id,
            $field->id,
            $variationName
        );
    }

    private function getTagsForVariation(
        Field $field,
        VersionInfo $versionInfo,
        string $variationName
    ): array {
        $contentId = $versionInfo->getContentInfo()->id;

        return [
            $this->cacheIdentifierGenerator->generateTag(self::IMAGE_VARIATION_IDENTIFIER),
            $this->cacheIdentifierGenerator->generateTag(self::IMAGE_VARIATION_NAME_IDENTIFIER, [$variationName]),
            $this->cacheIdentifierGenerator->generateTag(self::IMAGE_VARIATION_SITEACCESS_IDENTIFIER, [$this->siteAccess->name ?? 'default']),
            $this->cacheIdentifierGenerator->generateTag(self::IMAGE_VARIATION_CONTENT_IDENTIFIER, [$contentId]),
            $this->cacheIdentifierGenerator->generateTag(self::IMAGE_VARIATION_FIELD_IDENTIFIER, [$field->id]),
            $this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$contentId]),
            $this->cacheIdentifierGenerator->generateTag(self::CONTENT_VERSION_IDENTIFIER, [$contentId, $versionInfo->versionNo]),
        ];
    }

    public function getVariationNameTag(): string
    {
        return self::IMAGE_VARIATION_NAME_IDENTIFIER;
    }
}
