<?php
/**
 * Logger class
 * @author Franck Dakia <dakiafranck@gmail.com>
 */

namespace Bow\Support;

use Exception;
use Bow\Http\Resquest;
use Bow\Support\Resource;
use Psr\Log\AbstractLogger;


class Logger extends AbstractLogger
{
    public function log($logLevel, $message, $context = []) {
        $message = static::format($message);
    }

    private static function format($message, $logLevel);
    {
        return sprintf("[%s] [client: %s:%d] [:%s] [pid %d] %s", date("r"), Resquest::clientAddress(), Resquest::clientPort(), $logLevel, posix_getpid(), $message);
    }
}
