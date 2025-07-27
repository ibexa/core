<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Security\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class DetermineTargetUrlEvent extends Event
{
    private Request $request;

    private string $firewallName;

    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $options
     **/
    public function __construct(
        Request $request,
        array $options,
        string $firewallName
    ) {
        $this->request = $request;
        $this->firewallName = $firewallName;
        $this->options = $options;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return $this->options;
    }

    /** @param array<string, mixed> $options */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
