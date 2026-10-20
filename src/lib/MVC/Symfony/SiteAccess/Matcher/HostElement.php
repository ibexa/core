<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

class HostElement implements VersatileMatcher
{
    private ?SimplifiedRequest $request = null;

    /**
     * Number of elements to take into account.
     */
    private int $elementNumber;

    /**
     * Host elements used for matching as an array.
     *
     * @phpstan-var list<string>
     */
    private array $hostElements;

    /**
     * @param array<mixed>|int $elementNumber Number of elements to take into account.
     */
    public function __construct(array | int $elementNumber)
    {
        if (is_array($elementNumber)) {
            // DI config parser will create an array with 'value' => number
            $elementNumber = (int)current($elementNumber);
        }

        $this->elementNumber = (int)$elementNumber;
    }

    public function __sleep()
    {
        return ['elementNumber', 'hostElements'];
    }

    /**
     * Returns matching SiteAccess.
     *
     * @return string|false SiteAccess matched or false.
     */
    public function match(): string | bool
    {
        $elements = $this->getHostElements();

        return $elements[$this->elementNumber - 1] ?? false;
    }

    public function getName(): string
    {
        return 'host:element';
    }

    /**
     * Injects the request object to match against.
     *
     * @param SimplifiedRequest $request
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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function reverseMatch($siteAccessName): ?VersatileMatcher
    {
        $hostElements = explode('.', (string)$this->request?->getHost());
        $elementNumber = $this->elementNumber - 1;
        if (!isset($hostElements[$elementNumber])) {
            return null;
        }

        $hostElements[$elementNumber] = $siteAccessName;
        $this->getRequest()->setHost(implode('.', $hostElements));

        return $this;
    }

    /**
     * @phpstan-param list<string> $hostElements
     */
    public function setHostElements(array $hostElements): void
    {
        $this->hostElements = $hostElements;
    }

    /**
     * @phpstan-return list<string>
     */
    public function getHostElements(): array
    {
        if (isset($this->hostElements)) {
            return $this->hostElements;
        }

        if (!isset($this->request)) {
            return [];
        }

        $elements = explode('.', $this->request->getHost() ?? '');

        return $this->hostElements = $elements;
    }

    public function getElementNumber(): int
    {
        return $this->elementNumber;
    }
}
