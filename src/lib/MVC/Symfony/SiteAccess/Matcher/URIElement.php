<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;
use LogicException;

class URIElement implements VersatileMatcher, URILexer
{
    private ?SimplifiedRequest $request = null;

    /**
     * Number of elements to take into account.
     */
    private int $elementNumber;

    /**
     * URI elements used for matching as an array.
     *
     * @var array<string>
     */
    private array $uriElements;

    /**
     * @param array<mixed>|int $elementNumber Number of elements to take into account.
     */
    public function __construct(array|int $elementNumber)
    {
        if (is_array($elementNumber)) {
            // DI config parser will create an array with 'value' => number
            $elementNumber = (int)current($elementNumber);
        }

        $this->elementNumber = (int)$elementNumber;
    }

    public function __sleep()
    {
        return ['elementNumber', 'uriElements'];
    }

    /**
     * Returns matching SiteAccess.
     *
     * @return string|false SiteAccess matched or false.
     */
    public function match(): string|bool
    {
        try {
            return implode('_', $this->getURIElements());
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * Returns URI elements as an array.
     *
     * @throws \LogicException
     *
     * @return string[]
     */
    public function getURIElements(): array
    {
        if (isset($this->uriElements)) {
            return $this->uriElements;
        }

        if (!isset($this->request)) {
            return [];
        }

        $elements = array_slice(
            explode('/', (string)$this->request->getPathInfo()),
            1,
            $this->elementNumber
        );

        // If one of the elements is empty, we do not match.
        foreach ($elements as $element) {
            if ($element === '') {
                throw new LogicException('One of the URI elements was empty');
            }
        }

        if (count($elements) !== $this->elementNumber) {
            throw new LogicException('The number of provided elements to consider is different than the number of elements found in the URI');
        }

        return $this->uriElements = $elements;
    }

    public function getName(): string
    {
        return 'uri:element';
    }

    /**
     * Injects the request object to match against.
     *
     * @param \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest $request
     */
    public function setRequest(SimplifiedRequest $request): void
    {
        $this->request = $request;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the request is not set
     */
    public function getRequest(): SimplifiedRequest
    {
        if (null === $this->request) {
            throw new BadStateException(
                'request',
                sprintf(
                    'Missing required request context in %s matcher',
                    __CLASS__
                )
            );
        }

        return $this->request;
    }

    /**
     * Analyzes $uri and removes the SiteAccess part, if needed.
     *
     * @param string $uri The original URI
     *
     * @return string The modified URI
     */
    public function analyseURI($uri): string
    {
        $uriElements = '/' . implode('/', $this->getURIElements());
        if ($uri === $uriElements) {
            $uri = '';
        } elseif (str_starts_with($uri, $uriElements)) {
            $uri = mb_substr($uri, mb_strlen($uriElements));
        }

        return $uri;
    }

    /**
     * Analyses $linkUri when generating a link to a route, in order to have the SiteAccess part back in the URI.
     *
     * @param string $linkUri
     *
     * @return string The modified link URI
     */
    public function analyseLink($linkUri): string
    {
        // Joining slash between uriElements and actual linkUri must be present, except if $linkUri is empty.
        $joiningSlash = empty($linkUri) ? '' : '/';
        $linkUri = ltrim($linkUri, '/');
        $uriElements = implode('/', $this->getURIElements());

        return sprintf('/%s%s%s', $uriElements, $joiningSlash, $linkUri);
    }

    /**
     * Returns matcher object corresponding to $siteAccessName or null if non-applicable.
     *
     * Limitation: If the element number is > 1, we cannot predict how URI segments are expected to be built.
     * So we expect "_" will be reversed to "/"
     * e.g., foo_bar => foo/bar with elementNumber == 2
     * Hence if number of elements is different from the element number, we report as non-matched.
     *
     * @param string $siteAccessName
     *
     * @return \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIElement|null
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if request is not set
     */
    public function reverseMatch(string $siteAccessName): ?VersatileMatcher
    {
        $elements = $this->elementNumber > 1 ? explode('_', $siteAccessName) : [$siteAccessName];
        if (count($elements) !== $this->elementNumber) {
            return null;
        }

        $pathInfo = '/' . implode('/', $elements) . '/' . ltrim((string)$this->getRequest()->getPathInfo(), '/');
        $this->getRequest()->setPathinfo($pathInfo);

        return $this;
    }

    public function getElementNumber(): int
    {
        return $this->elementNumber;
    }

    /**
     * @param string[] $uriElements
     */
    public function setUriElements(array $uriElements): void
    {
        $this->uriElements = $uriElements;
    }
}
