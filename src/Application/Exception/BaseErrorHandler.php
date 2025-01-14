<?php

declare(strict_types=1);

namespace Bow\Application\Exception;

use PDOException;
use Bow\View\View;
use Bow\Http\Exception\HttpException;
use Bow\Validation\Exception\ValidationException;
use Policier\Exception\TokenExpiredException;
use Policier\Exception\TokenInvalidException;

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

    /**
     * Send the json as response
     *
     * @param string $data
     * @param mixed $code
     * @return mixed
     */
    protected function json($exception, $code = null)
    {
        if ($exception instanceof TokenInvalidException) {
            $code = 'TOKEN_INVALID';
        }

        if ($exception instanceof TokenExpiredException) {
            $code = 'TOKEN_EXPIRED';
        }

        if (is_null($code)) {
            if (method_exists($exception, 'getStatus')) {
                $code = $exception->getStatus();
            } else {
                $code = 'INTERNAL_SERVER_ERROR';
            }
        }

        if (app_env("APP_ENV") == "production" && $exception instanceof PDOException) {
            $message = 'An SQL error occurs. For security, we did not display the message.';
        } else {
            $message = $exception->getMessage();
        }

        $response = [
            'message' => $message,
            'code' => $code,
            'time' => date('Y-m-d H:i:s')
        ];

        $status = 500;

        if ($exception instanceof HttpException) {
            $status = $exception->getStatusCode();
            $response = array_merge($response, compact('status'));
            if ($exception instanceof ValidationException) {
                $response["errors"] = $exception->getErrors();
            }
        }

        if (app_env("APP_ENV") != "production") {
            $response["trace"] = $exception->getTrace();
        }

        response()->status($status);

        return die(json_encode($response));
    }
}
