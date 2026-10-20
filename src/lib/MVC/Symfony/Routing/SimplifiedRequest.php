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
     * The port the request is made on.
     */
    protected ?int $port = null;

    /**
     * @param string|null $scheme The request scheme (http or https).
     * @param string|null $host The host name.
     * @param int|string|null $port The port the request is made on.
     * @param string|null $pathinfo The path being requested relative to the executed script, always starts with a /.
     * @param array<mixed>|null $queryParams Parameters extracted from the query string.
     * @param string[]|null $languages Languages acceptable by the client browser, ordered by the user browser preferences.
     * @param array<string, array<string|null>>|null $headers Hash of request headers.
     */
    public function __construct(
        protected ?string $scheme = null,
        protected ?string $host = null,
        int | string | null $port = null,
        protected ?string $pathinfo = null,
        protected ?array $queryParams = null,
        protected ?array $languages = null,
        protected ?array $headers = null
    ) {
        parent::__construct();

        $this->port = $port !== null ? (int)$port : null;
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
