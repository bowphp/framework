<?php
/**
 * Logger class
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */

namespace Bow\Support;

use Bow\Support\Resource;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    /**
     * @var string
     */
    private static $debug;

    /**
     * @var string
     */
    private static $path;

    /**
     * log
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function log($level, $message, array $context = []) {
        if (static::$debug === "developpe") {
            echo static::htmlFormat($level, $message, $context);
        } else  if (static::$debug === "production") {
            Resource::append(static::$path, static::textFormat($level, $message, $context));
        }
    }

    /**
     * format
     *
     * @param $message
     * @param $level
     * @param $context
     * @return string
     */
    private function textFormat($level, $message, $context)
    {
        $message .= $context;
        return sprintf("[%s] [client: %s:%d] [:%s] [pid %d] %s", date("r"), $_SERVER["REMOTE_ADDR"], $_SERVER["REMOTE_PORT"], $level, posix_getpid(), $message);
    }

    /**
     * format
     *
     * @param string $message
     * @param string $level
     * @param array $context
     * @return string
     */
    private function htmlFormat($level, $message, array $context)
    {
        $content = "";

        $html = '
            <div style="display: inline-block; margin: auto; width: 800px">
                <div style="border: 1px solid #aaa; border-radius: 5px">
                    <h1>(<i>' . $level . '</i>)<b>' . $message . '</b> at ' . $context[1] . '</h1>
                </div>
                <div style="border: 1px solid #aaa; border-radius: 5px">
                    ' . $content . '
                </div>
            </div>
        ';
        return $html;
    }

    /**
     * @param string $debug develop | production
     * @param string $path
     */
    public static function register($debug, $path)
    {
        static::$debug = $debug;
        static::$path  = $path;
        set_error_handler([__CLASS__, "errorHandler"]);
        set_exception_handler([__CLASS__, "exceptionHandler"]);
    }

    /**
     * @param int $errno
     * @param string $errmsg
     * @param string $filename
     * @param int $linenum
     * @param array $context
     */
    private function errorHandler($errno, $errmsg, $filename, $linenum, $context)
    {
        static::addHandler($errno, $errmsg, $filename, $linenum, $context);
    }

    /**
     * @param \Exception $e
     */
    private function exceptionHandler(\Exception $e)
    {
        if (static::$debug === "developpe") {
            $trace = $e->getTrace();
        } else {
            $trace = $e->getTraceAsString();
        }

        $this->addHandler($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $trace);
    }

    /**
     * @param $errno
     * @param $errstr
     * @param $file
     * @param $linenum
     * @param $vars
     */
    private function addHandler($errno, $errstr, $file, $linenum, $vars)
    {
        // information sur le contexte de l'erreur
        $context = [
            "filename" => $file,
            "line"     => $linenum,
            "context"  => $vars
        ];

        // switch sur $errno (le numero de l'erreur)
        switch($errno) {
            case E_ERROR:
            case E_USER_ERROR:
                $this->error($errstr,  $context);
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $this->warning($errstr,  $context);
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $this->notice($errstr, $context);
                break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $this->alert($errstr,  $context);
                break;
            case E_CORE_ERROR:
            case E_CORE_WARNING:
                $this->critical($errstr,  $context);
                break;
        }
    }
}