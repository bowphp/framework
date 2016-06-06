<?php
namespace Bow\Support\Resource\Ftp;

class FTP
{
    /**
     * @var Resource
     */
    private $connection;

    /**
     * @var FtpWrapper
     */
    private $wrapper;
    /**
     * @return static
     */
    public static function configure()
    {
        return new static();
    }

    public function __construct()
    {
        $this->wrapper = new FtpWrapper();
    }
}