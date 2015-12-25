<?php

namespace System\Support;

use Exception;
use System\Support\Resource;
use System\Http\Resquest;

class Logger
{
    
    private static function open($savePath, $sessionName)
    {
        Resource::append();
    }
    
    private static function close()
    {

    }

    private static function write($sessionId, $data)
    {

    }
    
    public static function error($message)
    {

    }

    public static function warning($message)
    {

    }

    public static function info($message)
    {
        
    }

    public static function notice($message)
    {

    }

    public static function run($logDirectories)
    {

        set_error_handler([__CLASS__, 'errorHandler']);
        set_exception_handler([__CLASS__, "exceptionHandler"]);
    
    }

    public static function log($message)
    {
        $message = static::format($message);
    }

    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {

        $type = "error";
        
        switch ((int) $errno) {
            
            case E_ERROR:
            case E_USER_ERROR:
                $type = "fatal";
                break;
        
            case E_WARNING:
            case E_USER_WARNING:
                $type = "warning";
                break;
        
            case E_NOTICE:
            case E_USER_NOTICE:
                $type = "notice";
        
                break;
        }

        static::$type("[$type] $errstr at line $errline in $errfile");
    
    }

    public function exceptionHandler(Exception $e)
    {

        $type = "error";
        
        switch ($e->getCode()) {

            case E_ERROR:
            case E_USER_ERROR:
                $type = "fatal";
                break;
        
            case E_WARNING:
            case E_USER_WARNING:
                $type = "warning";
                break;
        
            case E_NOTICE:
            case E_USER_NOTICE:
                $type = "notice";
                break;
        
        }

        static::$type("[$type]: " . $e->getMessage() . " at line " . $e->getLine() . " in " . $e->getFile());
    
    }

    private static function format($message)
    {
        return sprintf("[%s] [client: %s:%d [pid %d] %s", date("r"), Resquest::clientAddress(), Resquest::clientPort(), posix_getpid(), $message);
    }
}
