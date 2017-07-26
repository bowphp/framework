<?php
namespace Bow\Resource;

use BadMethodCallException;
use Bow\Resource\Exception\ResourceException;
use function realpath;

class MountFilesystem
{
    /**
     * @var
     */
    private $basedir;

    /**
     * Filesystem constructor.
     * @param $basedir
     */
    public function __construct($basedir)
    {
        $this->basedir = realpath($basedir);
    }

    /**
     * UploadFile, fonction permettant de uploader un fichier
     *
     * @param array $file information sur le fichier, $_FILES
     * @param string $location
     * @param int $size
     * @param array $extension
     * @param callable $cb
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function store($file, $location, $size, array $extension, callable $cb)
    {
        if (!is_uploaded_file($file['tmp_name'])) {
            return call_user_func_array($cb, ['error']);
        }

        if (is_string($size)) {
            if (!preg_match('/^([0-9]+)(k|m)$/', strtolower($size), $match)) {
                throw new \InvalidArgumentException('Taille invalide.', E_USER_ERROR);
            }

            $conv = 1;
            array_shift($match);

            if ($match[1] == 'm') {
                $conv = 1000;
            }

            $size = $match[0] * $conv;
        }

        if ($file['size'] > $size) {
            return call_user_func_array($cb, ['size']);
        }

        if (!in_array(pathinfo($file['name'], PATHINFO_EXTENSION), $extension, true)) {
            return call_user_func_array($cb, ['extension']);
        }

        $r = $this->copy($file['tmp_name'], $location);

        return call_user_func_array($cb, [$r == true ? false : 'uploaded']);
    }

    /**
     * Ecrire à la suite d'un fichier spécifier
     *
     * @param string $file nom du fichier
     * @param string $content content a ajouter
     * @return bool
     */
    public function append($file, $content)
    {
        return file_put_contents($file, $content, FILE_APPEND);
    }

    /**
     * Ecrire au début d'un fichier spécifier
     *
     * @param string $file
     * @param string $content
     * @return bool
     */
    public function prepend($file, $content)
    {
        $tmp_content = file_get_contents($file);

        $this->put($file, $content);
        return $this->append($file, $tmp_content);
    }

    /**
     * Put
     *
     * @param $file
     * @param $content
     * @throws ResourceException
     * @return bool
     */
    public function put($file, $content)
    {
        $file = $this->resolvePath($file);
        $dirname = dirname($file);

        $this->makeDirectory($dirname);
        return file_put_contents($file, $content);
    }

    /**
     * Supprimer un fichier
     *
     * @param string $file
     * @return boolean
     */
    public function delete($file)
    {
        $file = $this->resolvePath($file);

        if (is_dir($file)) {
            return @rmdir($file);
        }

        return @unlink($file);
    }

    /**
     * Alias sur readInDir
     *
     * @param string $dirname
     * @return array
     */
    public function files($dirname)
    {
        $dirname = $this->resolvePath($dirname);
        $directoryContents = glob($dirname."/*");

        return array_filter($directoryContents, function($file)
        {
            return filetype($file) == "file";
        });
    }

    /**
     * Lire le contenu du dossier
     *
     * @param string $dirname
     * @return array
     */
    public function directories($dirname)
    {
        $directoryContents = glob($this->resolvePath($dirname)."/*");

        return array_filter($directoryContents, function($file)
        {
            return filetype($file) == "dir";
        });
    }

    /**
     * Crée un répertoire
     *
     * @param string $dirname
     * @param int $mode
     * @param bool $recursive
     * @return boolean
     */
    public function makeDirectory($dirname, $mode = 0777, $recursive = false)
    {
        if (is_bool($mode)) {
            $recursive = $mode;
            $mode = 0777;
        }

        return @mkdir($this->resolvePath($dirname), $mode, $recursive);
    }

    /**
     * Récuper le contenu du fichier
     *
     * @param string $filename
     * @return null|string
     */
    public function get($filename)
    {
        $filename = $this->resolvePath($filename);

        if (!(is_file($filename) && stream_is_local($filename))) {
            return null;
        }

        return file_get_contents($filename);
    }

    /**
     * Copie le contenu d'un fichier source vers un fichier cible.
     *
     * @param string $targerFile
     * @param string $sourceFile
     * @return bool
     */
    public function copy($targerFile, $sourceFile)
    {
        if (!$this->exists($targerFile)) {
            throw new \RuntimeException("$targerFile n'exist pas.", E_ERROR);
        }

        if (!$this->exists($sourceFile)) {
            $this->makeDirectory(dirname($sourceFile), true);
        }

        return file_put_contents($sourceFile, $this->get($targerFile));
    }

    /**
     * Rénomme ou déplace un fichier source vers un fichier cible.
     *
     * @param $targerFile
     * @param $sourceFile
     */
    public function move($targerFile, $sourceFile)
    {
        $this->copy($targerFile, $sourceFile);
        $this->delete($targerFile);
    }

    /**
     * Vérifie l'existance d'un fichier
     *
     * @param $filename
     * @return bool
     */
    public function exists($filename)
    {
        $filename = $this->resolvePath($filename);

        if (is_dir($filename)) {
            $tmp = getcwd();
            $r = chdir($filename);
            chdir($tmp);

            return $r;
        }

        return file_exists($filename);
    }

    /**
     * L'extension du fichier
     *
     * @param $filename
     * @return string
     */
    public function extension($filename)
    {
        if ($this->exists($filename)) {
            return pathinfo($this->resolvePath($filename), PATHINFO_EXTENSION);
        }

        return null;
    }

    /**
     * isFile aliase sur is_file.
     *
     * @param $filename
     * @return bool
     */
    public function isFile($filename)
    {
        return is_file($this->resolvePath($filename));
    }

    /**
     * isDirectory aliase sur is_dir.
     *
     * @param $dirname
     * @return bool
     */
    public function isDirectory($dirname)
    {
        return is_dir($this->resolvePath($dirname));
    }

    /**
     * Permet de résolver un path.
     * Donner le chemin absolute d'un path
     *
     * @param $filename
     * @return string
     */
    public function resolvePath($filename)
    {
        if (preg_match('~^'.$this->basedir.'~', $filename)) {
            return $filename;
        }

        return rtrim($this->basedir, '/').'/'.ltrim($filename, '/');
    }
}