<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Routing;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * @property-read string $scheme @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see SimplifiedRequest::getScheme()} instead.
 * @property-read string $host @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see SimplifiedRequest::getHost()} instead.
 * @property-read string $port @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see SimplifiedRequest::getPort()} instead.
 * @property-read string $pathinfo @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see SimplifiedRequest::getPathInfo()} instead.
 * @property-read array $queryParams @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see SimplifiedRequest::getQueryParams()} instead.
 * @property-read array $languages @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see SimplifiedRequest::getLanguages()} instead.
 * @property-read array $headers @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see SimplifiedRequest::getHeaders()} instead.
 */
class SimplifiedRequest extends ValueObject
{
    /**
     * The request scheme (http or https).
     *
     * @var string
     */
    protected $scheme;

    /**
     * The host name.
     *
     * @var string
     */
    protected $host;

    /**
     * The port the request is made on.
     *
     * @var string
     */
    protected $port;

    /**
     * The path being requested relative to the executed script.
     * The path info always starts with a /.
     *
     * @var string
     */
    protected $pathinfo;

    /**
     * Array of parameters extracted from the query string.
     *
     * @var array
     */
    protected $queryParams;

    /**
     * List of languages acceptable by the client browser.
     * The languages are ordered in the user browser preferences.
     *
     * @var array
     */
    protected $languages;

    /**
     * Hash of request headers.
     *
     * @var array
     */
    protected $headers;

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @param array $languages
     */
    public function setLanguages(array $languages)
    {
        $this->languages = $languages;
    }

    /**
     * @param string $pathinfo
     */
    public function setPathinfo($pathinfo)
    {
        $this->pathinfo = $pathinfo;
    }

    /**
     * @param string $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @param array $queryParams
     */
    public function setQueryParams(array $queryParams)
    {
        $this->queryParams = $queryParams;
    }

    /**
     * @param string $scheme
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
    }

    /**
     * Constructs a SimplifiedRequest object from a standard URL (http://www.example.com/foo/bar?queryParam=value).
     *
     * @param string $url
     *
     * @internal
     *
     * @return \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest
     */
    public static function fromUrl($url)
    {
        $elements = parse_url($url);
        $elements['pathinfo'] = isset($elements['path']) ? $elements['path'] : '';

        if (isset($elements['query'])) {
            parse_str($elements['query'], $queryParams);
            $elements['queryParams'] = $queryParams;
        }

        // Remove unwanted keys returned by parse_url() so that we don't have them as properties.
        unset($elements['path'], $elements['query'], $elements['user'], $elements['pass'], $elements['fragment']);

        return new static($elements);
    }

    public function __sleep()
    {
        // Clean up headers for serialization not have a too heavy string (i.e. for ESI/Hinclude tags).
        $this->headers = [];

        return ['scheme', 'host', 'port', 'pathinfo', 'queryParams', 'languages', 'headers'];
    }

    /**
     * The request scheme - http or https.
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * The path being requested relative to the executed script.
     */
    public function getPathInfo(): string
    {
        return $this->pathinfo;
    }

    /**
     * @return array<mixed>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @return string[]
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * @return array<string>|null
     */
    public function getHeader(string $headerName): ?array
    {
        return $this->headers[$headerName] ?? null;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}

class_alias(SimplifiedRequest::class, 'eZ\Publish\Core\MVC\Symfony\Routing\SimplifiedRequest');
