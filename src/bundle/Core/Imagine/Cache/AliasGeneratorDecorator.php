<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\Cache;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Variation\Values\Variation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Persistence Cache layer for AliasGenerator.
 */
class AliasGeneratorDecorator implements VariationHandler, SiteAccessAware
{
    private const string IMAGE_VARIATION_IDENTIFIER = 'image_variation';
    private const string IMAGE_VARIATION_SITEACCESS_IDENTIFIER = 'image_variation_siteaccess';
    private const string IMAGE_VARIATION_CONTENT_IDENTIFIER = 'image_variation_content';
    private const string IMAGE_VARIATION_FIELD_IDENTIFIER = 'image_variation_field';
    private const string IMAGE_VARIATION_NAME_IDENTIFIER = 'image_variation_name';
    private const string CONTENT_IDENTIFIER = 'content';
    private const string CONTENT_VERSION_IDENTIFIER = 'content_version';

    private VariationHandler $aliasGenerator;

    private TagAwareAdapterInterface $cache;

    private ?SiteAccess $siteAccess = null;

    private RequestContext $requestContext;

    private CacheIdentifierGeneratorInterface $cacheIdentifierGenerator;

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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
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
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess|null $siteAccess
     */
    public function setSiteAccess(?SiteAccess $siteAccess = null): void
    {
        $this->siteAccess = $siteAccess;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function getCacheKey(Field $field, VersionInfo $versionInfo, string $variationName): string
    {
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

    /**
     * @return string[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function getTagsForVariation(Field $field, VersionInfo $versionInfo, string $variationName): array
    {
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
