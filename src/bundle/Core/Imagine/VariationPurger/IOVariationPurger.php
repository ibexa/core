<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

use Ibexa\Bundle\Core\Imagine\Cache\AliasGeneratorDecorator;
use Ibexa\Contracts\Core\Variation\VariationPurger;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\Persistence\Cache\Identifier\CacheIdentifierGeneratorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

/**
 * Purges image variations using the IOService.
 *
 * Depends on aliases being stored in their own folder, with each alias folder mirroring the original files structure.
 */
class IOVariationPurger implements VariationPurger, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private IOServiceInterface $io;

    private TagAwareAdapterInterface $cache;

    private CacheIdentifierGeneratorInterface $cacheIdentifierGenerator;

    private AliasGeneratorDecorator $aliasGeneratorDecorator;

    public function __construct(
        IOServiceInterface $io,
        TagAwareAdapterInterface $cache,
        CacheIdentifierGeneratorInterface $cacheIdentifierGenerator,
        AliasGeneratorDecorator $aliasGeneratorDecorator
    ) {
        $this->io = $io;
        $this->cache = $cache;
        $this->cacheIdentifierGenerator = $cacheIdentifierGenerator;
        $this->aliasGeneratorDecorator = $aliasGeneratorDecorator;
    }

    public function purge(array $aliasNames): void
    {
        $variationNameTag = $this->aliasGeneratorDecorator->getVariationNameTag();

        foreach ($aliasNames as $aliasName) {
            $directory = "_aliases/$aliasName";
            $this->io->deleteDirectory($directory);

            $variationTag = $this->cacheIdentifierGenerator->generateTag($variationNameTag, [$aliasName]);
            $this->cache->invalidateTags([$variationTag]);

            if (isset($this->logger)) {
                $this->logger->info("Purging alias directory $directory");
            }
        }
    }
}
