<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;
use LogicException;

class URIElement implements VersatileMatcher, URILexer
{
    /** @var \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest */
    private $request;

    /**
     * Number of elements to take into account.
     *
     * @var int
     */
    private $elementNumber;

    /**
     * URI elements used for matching as an array.
     *
     * @var array
     */
    private $uriElements;

    /**
     * Constructor.
     *
     * @param int|array $elementNumber Number of elements to take into account.
     */
    public function __construct($elementNumber)
    {
        if (is_array($elementNumber)) {
            // DI config parser will create an array with 'value' => number
            $elementNumber = current($elementNumber);
        }

        $this->elementNumber = (int)$elementNumber;
    }

    public function __sleep()
    {
        return ['elementNumber', 'uriElements'];
    }

    /**
     * Returns matching Siteaccess.
     *
     * @return string|false Siteaccess matched or false.
     */
    public function match()
    {
        try {
            return implode('_', $this->getURIElements());
        } catch (LogicException $e) {
            return false;
        }
    }

    /**
     * Returns URI elements as an array.
     *
     * @throws \LogicException
     *
     * @return array
     */
    protected function getURIElements()
    {
        if (isset($this->uriElements)) {
            return $this->uriElements;
        } elseif (!isset($this->request)) {
            return [];
        }

        $elements = array_slice(
            explode('/', $this->request->getPathInfo()),
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
    public function setRequest(SimplifiedRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @return \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Analyses $uri and removes the siteaccess part, if needed.
     *
     * @param string $uri The original URI
     *
     * @return string The modified URI
     */
    public function analyseURI($uri)
    {
        $uriElements = '/' . implode('/', $this->getURIElements());
        if ($uri == $uriElements) {
            $uri = '';
        } elseif (strpos($uri, $uriElements) === 0) {
            $uri = mb_substr($uri, mb_strlen($uriElements));
        }

        return $uri;
    }

    /**
     * Analyses $linkUri when generating a link to a route, in order to have the siteaccess part back in the URI.
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

        return "/{$uriElements}{$joiningSlash}{$linkUri}";
    }

    /**
     * Returns matcher object corresponding to $siteAccessName or null if non applicable.
     *
     * Limitation: If the element number is > 1, we cannot predict how URI segments are expected to be built.
     * So we expect "_" will be reversed to "/"
     * e.g. foo_bar => foo/bar with elementNumber == 2
     * Hence if number of elements is different than the element number, we report as non matched.
     *
     * @param string $siteAccessName
     *
     * @return \Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\URIElement|null
     */
    public function reverseMatch($siteAccessName)
    {
        $elements = $this->elementNumber > 1 ? explode('_', $siteAccessName) : [$siteAccessName];
        if (count($elements) !== $this->elementNumber) {
            return null;
        }

        $pathinfo = '/' . implode('/', $elements) . '/' . ltrim((string)$this->request->getPathInfo(), '/');
        $this->request->setPathinfo($pathinfo);

        return $this;
    }
}
