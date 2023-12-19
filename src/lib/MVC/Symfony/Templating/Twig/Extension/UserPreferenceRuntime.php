<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\UserPreferenceService;
use Twig\Extension\RuntimeExtensionInterface;

final class UserPreferenceRuntime implements RuntimeExtensionInterface
{
    private UserPreferenceService $userPreferenceService;

    public function __construct(
        UserPreferenceService $userPreferenceService
    ) {
        $this->userPreferenceService = $userPreferenceService;
    }

    public function getUserPreferenceValue(string $identifier, string $default): string
    {
        try {
            return $this->userPreferenceService->getUserPreference($identifier)->value;
        } catch (NotFoundException|UnauthorizedException $e) {
            return $default;
        }
    }

    public function hasUserPreference(string $identifier): bool
    {
        try {
            $this->userPreferenceService->getUserPreference($identifier);

            return true;
        } catch (NotFoundException|UnauthorizedException $e) {
            return false;
        }
    }
}
