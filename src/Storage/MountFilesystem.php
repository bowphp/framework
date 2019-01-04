<?php

namespace Bow\Storage;

use Bow\Http\UploadFile;
use Bow\Storage\Contracts\FilesystemInterface;
use InvalidArgumentException;

class MountFilesystem implements FilesystemInterface
{
    /**
     * The base work directory
     *
     * @var
     */
    private $basedir;

    /**
     * Filesystem constructor.
     *
     * @param $basedir
     */
    public function __construct($basedir)
    {
        $this->basedir = realpath($basedir);
    }

    /**
     * UploadFile, fonction permettant de uploader un fichier
     *
     * @param  UploadFile  $file
     * @param  string  $location
     * @param  array   $option
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function store(UploadFile $file, $location = null, array $option = [])
    {
        if (is_array($location)) {
            $option = $location;
            $location = null;
        }
        
        if (isset($option['as'])) {
            $filename = $option['as'];
        } else {
            $filename = $file->getHashName();
        }

        if (is_null($location)) {
            $location = $filename;
        } else {
            $location = trim($location, '/').'/'.$filename;
        }

        $this->put($location, $file->getContent());
    }

    /**
     * Ecrire à la suite d'un fichier spécifier
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function append($file, $content)
    {
        return file_put_contents($file, $content, FILE_APPEND);
    }

    /**
     * Ecrire au début d'un fichier spécifier
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws
     */
    public function prepend($file, $content)
    {
        $tmp_content = file_get_contents($file);

        $this->put($file, $content);

        return $this->append($file, $tmp_content);
    }

    /**
     * Put other file content in given file
     *
     * @param string $file
     * @param string $content
     * @return bool
     */
    public function put($file, $content)
    {
        $file = $this->path($file);

        $dirname = dirname($file);

        $this->makeDirectory($dirname);

        return file_put_contents($file, $content);
    }

    /**
     * Supprimer un fichier
     *
     * @param  string $file
     * @return boolean
     */
    public function delete($file)
    {
        $file = $this->path($file);

        if (is_dir($file)) {
            return @rmdir($file);
        }

        return @unlink($file);
    }

    /**
     * Liste les fichiers d'un dossier passé en paramètre
     *
     * @param  string $dirname
     * @return array
     */
    public function files($dirname)
    {
        $dirname = $this->path($dirname);

        $directory_contents = glob($dirname."/*");

        return array_filter($directory_contents, function ($file) {
            return filetype($file) == "file";
        });
    }

    /**
     * Liste les dossier d'un dossier passé en paramètre
     *
     * @param  string $dirname
     * @return array
     */
    public function directories($dirname)
    {
        $directory_contents = glob($this->path($dirname)."/*");

        return array_filter($directory_contents, function ($file) {
            return filetype($file) == "dir";
        });
    }

    /**
     * Crée un répertoire
     *
     * @param  string $dirname
     * @param  int    $mode
     * @param  bool   $recursive
     * @return boolean
     */
    public function makeDirectory($dirname, $mode = 0777, $recursive = false)
    {
        if (is_bool($mode)) {
            $recursive = $mode;

            $mode = 0777;
        }

        $dirname = $this->path($dirname);

        if (!is_dir($dirname)) {
            return @mkdir($dirname, $mode, $recursive);
        }

        return false;
    }

    /**
     * Récuper le contenu du fichier
     *
     * @param  string $filename
     * @return null|string
     */
    public function get($filename)
    {
        $filename = $this->path($filename);

        if (!(is_file($filename) && stream_is_local($filename))) {
            return null;
        }

        return file_get_contents($filename);
    }

    /**
     * Copie le contenu d'un fichier source vers un fichier cible.
     *
     * @param  string $target
     * @param  string $source
     * @return bool
     */
    public function copy($target, $source)
    {
        if (!$this->exists($target)) {
            throw new \RuntimeException("$target n'exist pas.", E_ERROR);
        }

        if (!$this->exists($source)) {
            $this->makeDirectory(dirname($source), true);
        }

        return file_put_contents($source, $this->get($target));
    }

    /**
     * Rénomme ou déplace un fichier source vers un fichier cible.
     *
     * @param $target
     * @param $source
     */
    public function move($target, $source)
    {
        $this->copy($target, $source);

        $this->delete($target);
    }

    /**
     * Vérifie l'existance d'un fichier
     *
     * @param  $filename
     * @return bool
     */
    public function exists($filename)
    {
        $filename = $this->path($filename);

        if (! $this->isDirectory($filename)) {
            return file_exists($filename);
        }
    
        $tmp = getcwd();

        $r = chdir($filename);

        chdir($tmp);

        return $r;
    }

    /**
     * L'extension du fichier
     *
     * @param  $filename
     * @return string
     */
    public function extension($filename)
    {
        if ($this->exists($filename)) {
            return pathinfo($this->path($filename), PATHINFO_EXTENSION);
        }

        return null;
    }

    /**
     * isFile aliase sur is_file.
     *
     * @param  $filename
     * @return bool
     */
    public function isFile($filename)
    {
        return is_file($this->path($filename));
    }

    /**
     * isDirectory aliase sur is_dir.
     *
     * @param  $dirname
     * @return bool
     */
    public function isDirectory($dirname)
    {
        return is_dir($this->path($dirname));
    }

    /**
     * Permet de résolver un path.
     * Donner le chemin absolute d'un path
     *
     * @param  $filename
     * @return string
     */
    public function path($filename)
    {
        if (preg_match('~^'.$this->basedir.'~', $filename)) {
            return $filename;
        }

        return rtrim($this->basedir, '/').'/'.ltrim($filename, '/');
    }
}
