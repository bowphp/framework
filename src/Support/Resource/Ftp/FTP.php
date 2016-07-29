<?php
namespace Bow\Support\Resource\Ftp;

/**
 * Class FTP
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support\Resource\Ftp
 */
class FTP
{
    /**
     * @var resource
     */
    private $tmp;

    /**
     * @var Resource
     */
    private $ftp;

    /**
     * FTP Constructeur
     */
    public function __construct()
    {
        $this->tmp = tmpfile();
    }

    /**
     * Permet de ce connecter au serveur FTP.
     *
     * @param string $hostname Le nom de serveur FTP
     * @param string $username Le nom d'utilisateur
     * @param string $password Le mot de passe de l'utilisteur.
     * @param int $port        Le port de connection
     * @param bool $tls        Si a true permet d'établir un connection sécuriré.
     * @param int $timeout     Le temps d'attente avant réponse
     *
     * @throws \ErrorException
     */
    public function connect($hostname, $username, $password, $port = 21, $tls = false, $timeout = 90)
    {
        if ($tls == true) {
            $this->ftp = ftp_ssl_connect($hostname, $port, $timeout);
        } else {
            $this->ftp = ftp_connect($hostname, $port, $timeout);
        }

        if ($this->ftp === null) {
            throw new \ErrorException('Impossible de ce connecté au serveur FTP.');
        }

        if (!ftp_login($this->ftp, $username, $password)) {
            throw new \ErrorException('Détaille de connection incorrecte.');
        }
    }

    /**
     * Vérifie si le chemin pointe sur le fichier.
     *
     * @param $filename
     * @return bool
     */
    public function isFile($filename)
    {
        return ftp_fget($this->ftp, $this->tmp, $filename, FTP_ASCII);
    }

    /**
     * Vérifie si le chemin pointe sur un fichier sur le serveur.
     *
     * @param string $dirname
     * @return bool
     */
    public function isDirectory($dirname)
    {
        $tmp = ftp_pwd($this->ftp);
        $r = false;

        if (ftp_chdir($this->ftp, $dirname)) {
            $r = true;
            ftp_chdir($this->ftp, $tmp);
        }

        return $r;
    }

    /**
     * Récuper le contenu d'un fichier sur le serveur FTP
     *
     * @param string $filename
     * @param null|string $to
     * @return bool|null|string
     */
    public function get($filename, $to = null)
    {
        if ($to === null) {

            if (!ftp_fget($this->ftp, $this->tmp, $filename, FTP_BINARY)) {
                return null;
            }

            return $this->readTmp();
        }

        return ftp_get($this->ftp, $to, $filename, FTP_BINARY);
    }

    /**
     * @param $filename
     * @param $to
     */
    public function put($filename, $to)
    {

    }

    /**
     * __call
     *
     * @param $method
     * @param array $arguments
     * @throws \ErrorException
     *
     * @return bool
     */
    public function __call($method, array $arguments)
    {
        if (!function_exists('ftp_' . $method)) {
            throw new \ErrorException('La methode ' . $method . ' est inconnu.', E_USER_ERROR);
        }

        array_unshift($arguments, $this->ftp);
        return call_user_func_array('ftp_' . $method, $arguments);
    }

    /**
     * Lecteur de contenu de TMp
     *
     * @return null|string
     */
    private function readTmp()
    {
        $content = '';

        while($line = fread($this->tmp, 1e3)) {
            $content .= $line;
        }

        return $content == '' ? null : $content;
    }

    /**
     * Déstructeur
     */
    public function __destroy()
    {
        fclose($this->tmp);
    }
}