<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Exception;

use Exception;
use Ibexa\Core\MVC\Symfony\View\View;

/**
 * Thrown when a view is attempted to be rendered without a template set.
 */
class NoViewTemplateException extends Exception
{
    private View $view;

    public function __construct(View $view)
    {
        $this->view = $view;
        parent::__construct(
            sprintf(
                "No view template was set to render the view with the '%s' view type. Check your view configuration.",
                $view->getViewType()
            )
        );
    }

    public function getView()
    {
        return $this->view;
    }
}
