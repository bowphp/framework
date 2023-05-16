<?php

declare(strict_types=1);

namespace Bow\Application\Exception;

use Bow\View\View;

class BaseErrorHandler
{
    /**
     * Render view as response
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    protected function render($view, $data = []): string
    {
        return View::parse($view, $data)->getContent();
    }
}
