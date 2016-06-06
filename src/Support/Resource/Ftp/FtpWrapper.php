<?php
namespace Bow\Support\Resource\Ftp;

class FtpWrapper
{
    /**
     * __call
     *
     * @param string $method    Le nom de la method a appele.
     * @param array  $arguments Les arguments a passés
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        return call_user_func_array("ftp_$method", $arguments);
    }
}