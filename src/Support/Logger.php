<?php
/**
 * Logger class
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */

namespace Bow\Support;

use Bow\Exception\LoggerException;
use Bow\Support\Resource;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    /**
     * @var string
     */
    private $debug;

    /**
     * @var string
     */
    private $path;

    /**
     * @param string $debug develop | production
     * @param string $path
     */
    public function __construct($debug, $path)
    {
        $this->debug = $debug;
        $this->path  = $path;
    }
    
    /**
     * @return Logger
     */
    public function register()
    {
        set_error_handler([$this, "errorHandler"]);
        set_exception_handler([$this, "exceptionHandler"]);
    }

    /**
     * log
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @throws LoggerException
     * @return mixed
     */
    public function log($level, $message, array $context = []) {
        if ($this->debug === "developpement") {
            echo static::htmlFormat($level, $message, $context);
        } else  if ($this->debug === "production") {
            $message .= "\nin " . $context["file"] . " at " . $context["line"] . "\n";
            Resource::append($this->path, static::textFormat($level, $message, $context["context"] . "\n"));
        } else {
            throw new LoggerException("debug: ". $this->debug . " n'est pas definir");
        }

        exit;
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
        return sprintf("[%s] [client: %s:%d] [%s] %s", date("Y-m-d H:i:s"), $_SERVER["REMOTE_ADDR"], $_SERVER["REMOTE_PORT"], $level, $message);
    }

    /**
     * format
     *
     * @param string $message
     * @param string $level
     * @param array $context
     * @return string
     */
    private function htmlFormat($level, $message, array $context = [])
    {
        $content = "";

        if (isset($context["context"])) {
            foreach ($context["context"] as $key => $errRef) {
                $key++;
                $func = "";
                $line = "";
                $file = "";
                if (isset($errRef["function"])) {
                    $func = $errRef["function"];
                }

                if (isset($errRef["type"])) {
                    $func = $errRef["class"] . "" . $errRef["type"] . "" . $func. "(";
                }

                if (isset($errRef["line"])) {
                    $line = $errRef["line"];
                }

                if (isset($errRef["file"])) {
                    $file = $errRef["file"];
                }

                if (isset($errRef["args"])) {
                    $len = count($errRef["args"]);
                    foreach($errRef["args"] as $k => $args) {
                        $func .= gettype($args);
                        if (gettype($args) === "string") {
                            $func .= "(" . $args . ")";
                        }
                        if (gettype($args) === "object") {
                            $func .= "(Closure)";
                        }
                        if ($k + 1 != $len) {
                            $func .= ", ";
                        }
                    }
                    $func .= ")";
                }

                $content .= "<div style=\"text-align: left; color: #000; border-bottom: 1px dotted #bbb\">$key: at " . $file . " <b><i>" . $func . "</i></b>:";
                $content .= $line . " </div>";
            }
        } else {
            $content = "Aucun context.";
        }

        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Bow - ' . $level . '</title>
            </head>
            <body>
            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: inline-block; margin: auto; background-color: #eee; padding: 5px; text-align: center">
                <div style="border: 1px solid #aaa; border-radius: 5px; padding: 8px; background-color: white; width: 950px; text-align: center; margin: auto">
                    <h1><i style="font-weight: normal;">' . ucfirst($level) . '</i>: <b> ' . ucwords($message) . '</b></h1>
                    <p>' . $context["file"] . ' at <i>line ' . $context["line"] . '</i></p>
                </div>
                <div style="font-family: courier; font-size: 13px; border: 1px solid #aaa; border-radius: 10px; padding: 15px; width: 1100px; margin: auto; margin-top: 8px;">
                    ' . $content . '
                </div>
            </div>
            </body>
            </html>
        ';

         return $html;
    }

    /**
     * @param int $errno
     * @param string $errmsg
     * @param string $filename
     * @param int $linenum
     * @param array $context
     */
    public function errorHandler($errno, $errmsg, $filename, $linenum, $context)
    {
        $this->addHandler($errno, $errmsg, $filename, $linenum, $context);
    }

    /**
     * @param \Exception $e
     */
    public function exceptionHandler(\Exception $e)
    {
        if ($this->debug === "developpement") {
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
            "file" => $file,
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