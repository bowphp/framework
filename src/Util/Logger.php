<?php

namespace System\Util;

class Logger
{
    private function open($savePath, $sessionName) {}
    private function close() {}
    private function write($sessionId, $data) {}
    public function error() {}
    public function warning() {}
    public function info() {}
    public function notice() {}
    public function run() {
        set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
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
            self::$type("[$type] $errstr at line $errline in $errfile");
        });
        set_exception_handler(function(\Exception $e) {
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
            self::log("[$type] " . $e->getMessage() . " at line " . $e->getLine() . " in " . $e->getFile());
        });
    }
}
