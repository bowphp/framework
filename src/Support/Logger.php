<?php
/**
 * Logger class
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */

namespace Bow\Support;

use Bow\Http\Request;
use Bow\Support\Resource;
use Psr\Log\AbstractLogger;


class Logger extends AbstractLogger
{
    /**
     * @var string
     */
    private $message;

    /**
     * log
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function log($level, $message, array $context = []) {
        $this->message = static::format($message, $level);
    }

    /**
     * format
     *
     * @param $message
     * @param $level
     * @return string
     */
    private static function format($message, $level)
    {
        return sprintf("[%s] [client: %s:%d] [:%s] [pid %d] %s", date("r"), Resquest::clientAddress(), Resquest::clientPort(), $level, posix_getpid(), $message);
    }
}