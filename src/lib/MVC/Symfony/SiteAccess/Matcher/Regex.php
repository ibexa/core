<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

abstract class Regex implements Matcher
{
    /**
     * Element that will be matched against the regex.
     */
    protected ?string $element = null;

    /**
     * Regular expression used for matching.
     */
    protected string $regex;

    /**
     * Item number to pick in regex.
     */
    protected int $itemNumber;

    protected SimplifiedRequest $request;

    protected string | false $matchedSiteAccess;

    /**
     * @param string $regex Regular Expression to use.
     * @param int $itemNumber Item number to pick in regex.
     */
    public function __construct(
        string $regex,
        int $itemNumber
    ) {
        $this->regex = $regex;
        $this->itemNumber = $itemNumber;
    }

    public function __sleep()
    {
        return ['regex', 'itemNumber', 'matchedSiteAccess'];
    }

    public function match(): string | bool
    {
        return $this->getMatchedSiteAccess();
    }

    /**
     * Returns matched SiteAccess.
     */
    protected function getMatchedSiteAccess(): string | false
    {
        if (isset($this->matchedSiteAccess)) {
            return $this->matchedSiteAccess;
        }

        preg_match(
            "@$this->regex@",
            $this->element,
            $match
        );

        $this->matchedSiteAccess = $match[$this->itemNumber] ?? false;

        return $this->matchedSiteAccess;
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
     * Injects an element to match against with the regexp.
     */
    public function setMatchElement(string $element): void
    {
        $this->element = $element;
    }

    public function getRegex(): string
    {
        return $this->regex;
    }

    public function getItemNumber(): int
    {
        return $this->itemNumber;
    }
}
