<?php
namespace Bow\Logger;

use Bow\Resource\Storage;
use Psr\Log\AbstractLogger;
use Bow\Exception\LoggerException;

/**
 * Class Logger
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Logger extends AbstractLogger
{
    /**
     * @var string
     */
    protected $mode;

    /**
     * @var string
     */
    private $path;

    /**
     * @param string $mode develop | production
     * @param string $path
     */
    public function __construct($mode, $path)
    {
        $this->mode = $mode;
        $this->path  = $path;
    }

    /**
     * @return Logger
     */
    public function register()
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        return $this;
    }

    /**
     * log
     *
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @throws LoggerException
     *
     * @return mixed
     */
    public function log($level, $message, array $context = []) {

        if (!in_array($this->mode, ['development', 'production'])) {
            throw new LoggerException($this->mode . ' n\'est pas définir');
        }

        if (!empty($context)) {
            $message = '\''. $message.'\'' . ' in ' . $context['file'] . ' at ' . $context['line'];
            if (isset($context['trace'])) {
                if (is_string($context['trace'])) {
                    $message .= $context['trace'];
                }
            }
        }

        Storage::append($this->path, static::textFormat($level, $message . '\n'));

        if ($this->mode === 'development') {
            die(static::htmlFormat($level, $message, $context));
        }

        return $this;
    }

    /**
     * format
     *
     * @param $message
     * @param $level
     *
     * @return string
     */
    private function textFormat($level, $message)
    {
        return sprintf("[%s] [client: %s:%d] [%s] %s", date('D Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'], $level, $message);
    }

    /**
     * format
     *
     * @param string $message
     * @param string $level
     * @param array $context
     *
     * @return string
     */
    private function htmlFormat($level, $message, array $context = [])
    {
        $content = '';
        $subErrorMessage = '...';

        if (isset($context['trace'])) {

            if (is_array($context['trace'])) {

                foreach ($context['trace'] as $key => $errRef) {
                    $func   = '';
                    $line   = '';
                    $file   = '';
                    $errRef = (array) $errRef;

                    if (isset($errRef['function'])) {
                        $func = $errRef['function'] . '(';
                    }

                    if (isset($errRef['type'])) {
                        $func = $errRef['class'] . '' . $errRef['type'] . '' . $func;
                    }

                    if (isset($errRef['line'])) {
                        $line = $errRef['line'];
                    }

                    if (isset($errRef['file'])) {
                        $file = $errRef['file'];
                    }

                    if (isset($errRef['args'])) {
                        if (is_array($errRef['args'])) {

                            $len = count($errRef['args']);

                            foreach($errRef['args'] as $k => $args) {
                                $func .= ucfirst(gettype($args));
                                if (gettype($args) === 'string') {
                                    $func .= '(\'' . $args . '\')';
                                }
                                if (gettype($args) === 'object') {
                                    if (is_callable($args)) {
                                        $func .= '(Closure)';
                                    } else {
                                        $func .= '(' . get_class($args) . ')';
                                    }
                                }
                                if ($k + 1 != $len) {
                                    $func .= ', ';
                                }
                            }
                        }
                        $func .= ')';
                    }

                    if (is_int($key)) {
                        $content .= '<div style="text-align: left; color: #000; border-bottom: 1px dotted #bbb">' . $key . '# at ' . $file . ' <b><i>' . $func . '</i></b>:';
                        $content .= $line . ' </div>';
                    }
                }
            } else {
                $content = $context['trace'];
            }

        } else {
            $content = '<i>Aucun context.</i>';
        }

        if (isset($context['file'], $context['line'])) {
            $subErrorMessage = $context['file'] . ' at <i>line ' . $context['line'];
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
                <div style="border: 1px solid #aaa; padding: 8px; width: 950px; text-align: center; margin: auto; background-image: linear-gradient(#ddd, #eee, #dedede); overflow: auto; box-sizing: border-box">
                    <h1><i style="font-weight: normal;">' . ucfirst($level) . '</i>: <b> ' . $message . '</b></h1>
                    <p>' . $subErrorMessage . '</i></p>
                </div>
                <div style="font-size: 13px; border: 1px solid #aaa; border-radius: 10px; padding: 15px; width: 1100px; margin: 8px auto;">
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
     * @param int $line
     */
    public function errorHandler($errno, $errmsg, $filename, $line)
    {
        $this->addHandler($errno, $errmsg, $filename, $line, []);
    }

    /**
     * @param \Exception|\ParseError $e
     */
    public function exceptionHandler($e)
    {
        if ($this->mode === 'development') {
            $trace = $e->getTrace();
        } else {
            $trace = $e->getTraceAsString();
        }
        $this->addHandler($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $trace);
    }

    /**
     * Ajout une exception personnalisé
     *
     * @param int $errno Le numéro d'erreur PHP
     * @param string $errstr Le message d'erreur.
     * @param string $file Le fichier dans lequel il y a eu l'erreur
     * @param int $line La ligne de l'erreur
     * @param string|array $trace L'information descriptif sur l'erreur
     */
    private function addHandler($errno, $errstr, $file, $line, $trace)
    {
        // information sur le contexte de l'erreur
        $context = [
            'file'   => $file,
            'line'   => $line,
            'trace'  => $trace
        ];

        // switch sur $errno (le numero de l'erreur)
        switch($errno) {
            case E_ERROR:
            case E_USER_ERROR:
            case E_CORE_ERROR:
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
                $this->emergency($errstr, $context);
                break;
        }
    }

    /**
     * Modifie le mode du logger.
     *
     * @param string $mode Le mode du logger
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        $this->register();
    }
}