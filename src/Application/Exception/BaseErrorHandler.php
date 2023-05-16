<?php

declare(strict_types=1);

namespace Bow\Application\Exception;

use Bow\View\View;

class BaseErrorHandler extends \Exception
{
    /**
     * Render view as response
     *
     * @param string $view
     * @param array $data
     * @return mixed
     */
    protected function render($view, $data = [])
    {
        return View::parse($view, $data)->getContent();
    }
}
