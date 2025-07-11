<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\View;

use Ibexa\Core\MVC\Symfony\View\LoginFormView;
use Ibexa\Core\MVC\Symfony\View\View;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @group mvc
 */
final class LoginFormViewTest extends AbstractViewTestCase
{
    public function testSetLastUsername(): void
    {
        /** @var \Ibexa\Core\MVC\Symfony\View\LoginFormView $view */
        $view = $this->createViewUnderTest();
        $view->setLastUsername('johndoe');

        self::assertEquals('johndoe', $view->getLastUsername());
    }

    public function testSetLastAuthenticationError(): void
    {
        $exception = $this->createMock(AuthenticationException::class);

        /** @var \Ibexa\Core\MVC\Symfony\View\LoginFormView $view */
        $view = $this->createViewUnderTest();
        $view->setLastAuthenticationError($exception);

        self::assertEquals($exception, $view->getLastAuthenticationException());
    }

    protected function createViewUnderTest($template = null, array $parameters = [], $viewType = 'full'): View
    {
        return new LoginFormView($template, $parameters, $viewType);
    }

    protected function getAlwaysAvailableParams(): array
    {
        return [
            'last_username' => null,
            'error' => null,
        ];
    }
}
