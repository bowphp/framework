<?php
namespace Bow\Http;
/**
 * Class UploadFile
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Http
 */
class UploadFile
{
    /**
     * @var array
     */
    private $file;

    /**
     * UploadFile constructor.
     *
     * @param array $file
     */
    public function __construct(array $file = [])
    {
        $this->file = $file;
    }

    /**
     * L'extension du fichier
     *
     * @return string
     */
    public function getExtension()
    {
        if (isset($this->file['name'])) {
            return array_pop(explode('.', $this->file['name']));
        }
        return null;
    }

    /**
     * L'extension du fichier
     *
     * @return string
     */
    public function getTypeMime()
    {
        if (isset($this->file['type'])) {
            return $this->file['type'];
        }
        return null;
    }

    /**
     * La taille du fichier
     *
     * @return mixed
     */
    public function getSize()
    {
        if (isset($this->file['size'])) {
            return $this->file['size'];
        }
        return 0;
    }

    /**
     * Vérifié si le fichier est valide
     *
     * @return bool
     */
    public function isValid()
    {
        return count($this->file) === 4;
    }

    /**
     * Vérifie si le fichier est uploader
     *
     * @return bool
     */
    public function isUploaded()
    {
        if (isset($this->file['tmp_name'], $this->file['error'])) {
            return false;
        }
        return is_uploaded_file($this->file['tmp_name']) && $this->file['error'] !== 0;
    }

    /**
     * Le nom principal du fichier
     *
     * @return string
     */
    public function getBasename()
    {
        if (isset($this->file['name'])) {
            return null;
        }
        return basename($this->file['name']);
    }

    /**
     * Le nom du fichier
     *
     * @return mixed
     */
    public function getFilename()
    {
        if (isset($this->file['name'])) {
            return null;
        }
        return $this->file['name'];
    }

    /**
     * Le contenu du fichier.
     *
     * @return string
     */
    public function getContent()
    {
        if (isset($this->file['tmp_name'])) {
            return null;
        }
        return file_get_contents($this->file['tmp_name']);
    }

    /**
     * Le nom hash md5 du fichier
     *
     * @return string
     */
    public function getHashName()
    {
        return md5($this->getBasename());
    }

    /**
     * Déplacer le fichier uploader dans un répertoire.
     *
     * @param string $to
     * @return bool
     */
    public function move($to, $filename = null)
    {
        if (isset($this->file['tmp_name'])) {
            return false;
        }
        $save_name = $this->file['tmp_name'];
        if (is_string($filename)) {
            $save_name = $filename;
        }
        if (! is_dir($to)) {
            throw new \Exception('Le dossier de sauvegarde n\'existe pas!');
        }
        return (bool) move_uploaded_file($save_name, $to);
    }
}