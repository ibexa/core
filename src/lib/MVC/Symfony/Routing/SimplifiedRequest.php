<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Routing;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

class SimplifiedRequest extends ValueObject
{
    /**
     * The request scheme (http or https).
     */
    protected ?string $scheme = null;

    /**
     * The host name.
     */
    protected ?string $host = null;

    /**
     * The port the request is made on.
     */
    protected ?int $port = null;

    /**
     * The path being requested relative to the executed script.
     * The path info always starts with a /.
     */
    protected ?string $pathinfo = null;

    /**
     * Array of parameters extracted from the query string.
     *
     * @var array<mixed>|null
     */
    protected ?array $queryParams = null;

    /**
     * List of languages acceptable by the client browser.
     * The languages are ordered in the user browser preferences.
     *
     * @var string[]|null
     */
    protected ?array $languages = null;

    /**
     * Hash of request headers.
     *
     * @var array<string, array<string|null>>|null
     */
    protected ?array $headers = null;

    /**
     * @param array<mixed>|null $queryParams
     * @param string[]|null $languages
     * @param array<string, array<string|null>>|null $headers
     */
    public function __construct(
        ?string $scheme = null,
        ?string $host = null,
        int | string | null $port = null,
        ?string $pathinfo = null,
        ?array $queryParams = null,
        ?array $languages = null,
        ?array $headers = null
    ) {
        parent::__construct();

        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port !== null ? (int)$port : null;
        $this->pathinfo = $pathinfo;
        $this->queryParams = $queryParams;
        $this->languages = $languages;
        $this->headers = $headers;
    }

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
     * @param int $port
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
     * @return SimplifiedRequest
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

        return new static(
            $elements['scheme'] ?? null,
            $elements['host'] ?? null,
            $elements['port'] ?? null,
            $elements['pathinfo'] ?? null,
            $elements['queryParams'] ?? null,
            $elements['languages'] ?? null,
            $elements['headers'] ?? null,
        );
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
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * The path being requested relative to the executed script.
     */
    public function getPathInfo(): ?string
    {
        return $this->pathinfo;
    }

    /**
     * @return array<mixed>|null
     */
    public function getQueryParams(): ?array
    {
        return $this->queryParams;
    }

    /**
     * @return string[]|null
     */
    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    /**
     * @return array<string|null>|null
     */
    public function getHeader(string $headerName): ?array
    {
        return $this->headers[$headerName] ?? null;
    }

    /**
     * @return array<string, array<string|null>>|null
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }
}
