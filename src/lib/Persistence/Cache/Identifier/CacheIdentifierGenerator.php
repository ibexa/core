<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Cache\Identifier;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class CacheIdentifierGenerator implements CacheIdentifierGeneratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    private const PLACEHOLDER = '-%s';

    /** @var string */
    private $prefix;

    /** @var array<string,string> */
    private $tagPatterns;

    /** @var array<string,string> */
    private $keyPatterns;

    public function __construct(
        string $prefix,
        array $tagPatterns,
        array $keyPatterns
    ) {
        $this->prefix = $prefix;
        $this->tagPatterns = $tagPatterns;
        $this->keyPatterns = $keyPatterns;
        $this->logger = new NullLogger();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function generateTag(
        string $patternName,
        array $values = [],
        bool $withPrefix = false
    ): string {
        if (!isset($this->tagPatterns[$patternName])) {
            throw new InvalidArgumentException($patternName, sprintf(
                'Undefined tag pattern "%s". Known pattern names are: "%s"',
                $patternName,
                implode('", "', array_keys($this->tagPatterns))
            ));
        }

        return $this->generate($this->tagPatterns[$patternName], $values, $withPrefix);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function generateKey(
        string $patternName,
        array $values = [],
        bool $withPrefix = false
    ): string {
        if (!isset($this->keyPatterns[$patternName])) {
            throw new InvalidArgumentException($patternName, sprintf(
                'Undefined key pattern "%s". Known pattern names are: "%s"',
                $patternName,
                implode('", "', array_keys($this->keyPatterns))
            ));
        }

        return $this->generate($this->keyPatterns[$patternName], $values, $withPrefix);
    }

    private function generate(
        string $pattern,
        array $values,
        bool $withPrefix = false
    ): string {
        if (empty($values)) {
            $cacheIdentifier = str_replace(self::PLACEHOLDER, '', $pattern);
        } else {
            $cacheIdentifier = vsprintf($pattern, $values);
        }

        if ($withPrefix) {
            $cacheIdentifier = $this->prefix . $cacheIdentifier;
        }

        $this->logger->debug(sprintf('Generated cache identifier: %s', $cacheIdentifier), [
            'values' => $values,
            'pattern' => $pattern,
            'prefix' => $withPrefix ? $this->prefix : null,
        ]);

        return $cacheIdentifier;
    }
}
