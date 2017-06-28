<?php
namespace Bow\Resource\Ftp;

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
        $this->tmp = tempnam(sys_get_temp_dir(), mt_rand());
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
        if ($tls === true) {
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
        return ftp_get($this->ftp, $this->tmp, $filename, FTP_ASCII);
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
            if (!ftp_get($this->ftp, $this->tmp, $filename, FTP_BINARY)) {
                return null;
            }

            return $this->readTmp();
        }

        return ftp_get($this->ftp, $to, $filename, FTP_BINARY);
    }

    /**
     * Date de dernière modification
     *
     * @param string $filename
     * @return int
     */
    public function lastModifyTime($filename)
    {
        return date('Y-m-d H:i:s', ftp_mdtm($this->ftp, $filename));
    }

    /**
     * Liste le contenu de dossier distant.
     *
     * @param string $dirname
     * @return array
     */
    public function listDirectory($dirname)
    {
        return ftp_nlist($this->ftp, $dirname);
    }

    /**
     * Liste le contenu de dossier distant de façon brute.
     *
     * @param string $dirname
     * @return array
     */
    public function rawListDirectory($dirname)
    {
        return ftp_rawlist($this->ftp, $dirname);
    }

    /**
     * @param string $filename
     * @param int $mode
     * @return bool
     */
    public function changePermission($filename, $mode)
    {
        return ftp_chmod($this->ftp, $mode, $filename);
    }

    /**
     * @return string
     */
    public function type()
    {
        return ftp_systype($this->ftp);
    }

    /**
     * @param string $dirname
     * @return bool
     */
    public function chdir($dirname)
    {
        return ftp_chdir($this->ftp, $dirname);
    }

    /**
     * Lecteur de contenu de TMp
     *
     * @return null|string
     */
    private function readTmp()
    {
        return file_get_contents($this->tmp);
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
     * Déstructeur
     */
    public function __destruct()
    {
        @unlink($this->tmp);
        ftp_close($this->ftp);
    }
}