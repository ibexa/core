<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\MatcherBuilderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;

/**
 * Base for Compound SiteAccess matchers.
 * All classes extending this one must implement a NAME class constant.
 *
 * @phpstan-type TMatcherName class-string<\Ibexa\Core\MVC\Symfony\SiteAccess\Matcher> | literal-string
 * @phpstan-type TCompoundMatcherConfig array<int, array{matchers: array<TMatcherName, array<mixed>>, match: string}>
 */
abstract class Compound implements CompoundInterface, URILexer
{
    public const string NAME = 'compound';

    /**
     * Collection of rules using the Compound matcher.
     *
     * @phpstan-var TCompoundMatcherConfig
     */
    protected array $config;

    /**
     * Matchers' map.
     * Consists of an array of matchers, grouped by ruleset (so an array of an array of matchers).
     *
     * @phpstan-var array<int, array<class-string<Matcher> | literal-string, Matcher>>
     */
    protected array $matchersMap;

    /** @var Matcher[] */
    protected array $subMatchers = [];

    protected MatcherBuilderInterface $matcherBuilder;

    protected SimplifiedRequest $request;

    /**
     * @phpstan-param TCompoundMatcherConfig $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->matchersMap = [];
    }

    public function setMatcherBuilder(MatcherBuilderInterface $matcherBuilder): void
    {
        $this->matcherBuilder = $matcherBuilder;
        foreach ($this->config as $i => $rule) {
            foreach ($rule['matchers'] as $matcherClass => $matchingConfig) {
                $this->matchersMap[$i][$matcherClass] = $matcherBuilder->buildMatcher($matcherClass, $matchingConfig, $this->request);
            }
        }
    }

    public function setRequest(SimplifiedRequest $request): void
    {
        $this->request = $request;
        foreach ($this->matchersMap as $ruleset) {
            foreach ($ruleset as $matcher) {
                $matcher->setRequest($request);
            }
        }
    }

    public function getRequest(): SimplifiedRequest
    {
        return $this->request;
    }

    public function analyseURI($uri)
    {
        foreach ($this->getSubMatchers() as $matcher) {
            if ($matcher instanceof URILexer) {
                $uri = $matcher->analyseURI($uri);
            }
        }

        return $uri;
    }

    public function analyseLink($linkUri)
    {
        foreach ($this->getSubMatchers() as $matcher) {
            if ($matcher instanceof URILexer) {
                $linkUri = $matcher->analyseLink($linkUri);
            }
        }

        return $linkUri;
    }

    public function getSubMatchers(): array
    {
        return $this->subMatchers;
    }

    public function setSubMatchers(array $subMatchers): void
    {
        $this->subMatchers = $subMatchers;
    }

    /**
     * Returns the matcher's name.
     * This information will be stored in the SiteAccess object itself to quickly be able to identify the matcher type.
     */
    public function getName(): string
    {
        return
           'compound:' .
           static::NAME . '(' .
           implode(
               ', ',
               array_keys($this->getSubMatchers())
           ) . ')';
    }

    /**
     * Serialization occurs when serializing the SiteAccess for subrequests.
     */
    public function __sleep()
    {
        // we don't need the whole matcher map and the matcher builder once serialized.
        // config property is not needed either as it's only necessary for matching.
        return ['subMatchers'];
    }
}
