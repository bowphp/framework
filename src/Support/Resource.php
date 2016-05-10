<?php
/**
 * @author Franck Dakia <dakiafranck@gmail.com>
 *
 * @package Bow\Support
 */

namespace Bow\Support;

use InvalidArgumentException;
use Bow\Exception\ResourceException;

class Resource
{
	/**
	 * Liste des constantes d'erreur pour l'upload de fichier.
	 */
	const ERROR = 5;
	const SUCCESS = 7;
	const WARNING = 6;
	const ERROR_EXTENSION_INVALIDE = 8;
	const ERROR_SIZE_INVALIDE = 9;
	const ERROR_UPLOAD_ERROR = 10;
	/**
	 * Variable de configuration
	 * 
	 * @var array
	 */
	private static $config = [];
	/**
	 * Répertoire de stockage
	 * 
	 * @var null
	 */
	private static $storageDir = null;
	/**
	 * Répertoire par defaut de upload
	 * 
	 * @var string
	 */
	private static $uploadDir = "public";
	/**
	 * Taille par defaut d'un fichier
	 * 
	 * @var int
	 */
	private static $fileSize = 20000000;
	/**
	 * Nom d'un fichier
	 * 
	 * @var null
	 */
	private static $uploadFileName = null;
	/**
	 * Liste des extensions par defaut
	 * 
	 * @var array
	 */ 
	private static $fileExtension = ["png", "jpg"];
    /**
	 * Modifier le nom par defaut du file uploader.
	 * 
	 * @param string $filename
	 * @return static
	 */
	public static function setUploadFileName($filename)
	{
		static::$uploadFileName = $filename;
	}

	/**
	 * Modifie la liste des extensions valides
	 * 
	 * @param mixed $extension
	 */
	public static function setUploadFileExtension($extension)
	{
		if (is_array($extension)) {
			static::$fileExtension = $extension;
		} else {
			static::$fileExtension = func_get_args();
		}
	}

	/**
	 * setUploadedDir, fonction permettant de rédéfinir le répertoir d'upload
	 * 
	 * @param string:path, le chemin du dossier de l'upload
	 * @throws InvalidArgumentException
	 */
	public static function setUploadDirectory($path)
	{
		if (is_string($path)) {
			static::$uploadDir = $path;
		} else {
			throw new InvalidArgumentException("L'argument donnée a la fontion doit etre un entier");
		}
	}

	/**
	 * Modifie la taille prédéfinie de l'image à uploader.
	 * 
	 * @param integer $size
	 * @throws InvalidArgumentException
	 */
	public static function setUploadFilesize($size)
	{
		if (is_int($size)) {
			static::$fileSize = $size;
		} else {
			throw new InvalidArgumentException("L'argument donnée à la fonction doit être de type entier");
		}
	}

	/**
	 * UploadFile, fonction permettant de uploader un fichier
	 *
	 * @param array $file information sur le fichier, $_FILES
	 * @param callable|null $cb
	 * @param string $hash=null
	 * @return mixed
	 */
	public static function store($file, $cb = null, $hash = null)
	{
		if (!is_object($file) && !is_array($file)) {
			Util::launchCallBack($cb, [new InvalidArgumentException("Parametre invalide <pre>" . var_export($file, true) ."</pre>. Elle doit etre un tableau ou un object StdClass")]);
		}
		
		if (empty($file)) {
			Util::launchCallBack($cb, [new InvalidArgumentException("Le fichier a uploader n'existe pas")]);
		}
		
		$file = (object) $file;

		// Si le fichier est bien dans le répertoire tmp de PHP
		if (is_uploaded_file($file->tmp_name)) {
			
			$dirPart = explode("/", static::$uploadDir);
			$end = array_pop($dirPart);
			
			if ($end == "") {
				static::$uploadDir = implode(DIRECTORY_SEPARATOR, $dirPart);
			} else {
				static::$uploadDir = implode(DIRECTORY_SEPARATOR, $dirPart) .DIRECTORY_SEPARATOR. $end;
			}

			if (!is_dir(static::$uploadDir)) {
				@mkdir(static::$uploadDir, 0766);
			}

			// Si le fichier est bien uploader, avec aucune error
			if ($file->error === 0) {
				if ($file->size <= static::$fileSize) {
					$pathInfo = (object) pathinfo($file->name);
					if (in_array($pathInfo->extension, static::$fileExtension)) {
						if ($hash !== null) {
							if (static::$uploadFileName !== null) {
								$filename = hash($hash, static::$uploadFileName);
							} else {
								$filename = hash($hash, uniqid(rand(null, true)));
							}
						} else {
							if (static::$uploadFileName !== null) {
								$filename = static::$uploadFileName;
							} else {
								$filename = $pathInfo->filename;
							}
						}
						// Déplacement du fichier tmp vers le dossier d'upload
						move_uploaded_file($file->tmp_name, static::$uploadDir . "/" . $filename . '.' . $pathInfo->extension);
						
						// Status, fichier uploadé
						$status = static::SUCCESS;
					} else {
						// status, extension du fichier
						$status = static::ERROR_EXTENSION_INVALIDE;
					}
				} else {
					// status, la taille est invalide
					$status = static::ERROR_SIZE_INVALIDE;
				}

			} else {
				// status, fichier erroné.
				$status = static::ERROR_UPLOAD_ERROR;
			}

		} else {
			// status, fichier non uploadé
			$status = static::ERROR_UPLOAD_ERROR;
		}

		if ($cb !== null) {
			call_user_func_array($cb, [(object) $status, isset($filename) ? $filename : null, isset($ext) ? $ext : null]);
		} else {
			return (object) $status;
		}

		return null;
	}

	/**
	 * Ecrire dans le fichier spécifier
	 * 
	 * @param string $resource
	 * @param string $content
     * @throws ResourceException
	 * @return boolean
	 */
    private static function write($resource, $content)
    {
		$status = null;

    	if (is_resource($resource)) {
	        $status = fwrite($resource, $content);
    	} else {
            throw new ResourceException("Impossible d'écrire dans le fichier.", E_ERROR);
    	}

		static::closeFile($resource);

        return $status;
    }

	/**
	 * Ecrire à la suite d'un fichier spécifier
	 * 
	 * @param string $file nom du fichier
	 * @param string $content content a ajouter
	 */
    public static function append($file, $content)
    {
        static::write(static::open($file, "a"), $content);
    }

	/**
	 * Ecrire au début d'un fichier spécifier
	 * 
	 * @param string $file
	 * @param string $content
	 */
    public static function prepend($file, $content)
    {
        $tmp_content = file_get_contents($file);
        
        static::put($file, $content);
        static::append($file, $tmp_content);
    }

    public static function put($file, $content)
    {
        static::write(static::open($file, "w+"), $content);
    }
	/**
	 * Supprimer un fichier
	 * 
	 * @param string $file
	 * @return boolean
	 */
    public static function delete($file)
    {
        return @unlink($file);
    }

	/**
	 * Alias sur readInDir
	 * 
	 * @param string $filename
	 * @return array
	 */
    public static function files($filename)
    {
        return static::readIndir($filename, "file");
    }

	/**
	 * Alias sur readInDir
	 * 
	 * @param string $dirname
	 * @return array
	 */
    public static function directories($dirname)
    {
        return static::readIndir($dirname, "dir");
    }

	/**
	 * Crée un répertoire
	 * 
	 * @param string $files
     * @param int $mode
	 * @param bool $recursive
	 * @return boolean
	 */
    public static function mkdir($files, $mode = 0777, $recursive = false)
    {
        if (is_bool($mode)) {
            $recursive = $mode;
        }

        if ($recursive === true) {
            $status = @mkdir($files, 0777, true);
        } else {
            $status = @mkdir($files, 0777);
        }

		return $status;
    }

	/**
	 * Supprime un répertoire.
	 * 
	 * @param string $directory
	 * @param bool $recursive
	 * @return boolean
	 */
    public static function rmdir($directory, $recursive = false)
    {
		if ($recursive === true) {
            if (!is_dir($directory)) {

                $dirParts = explode(Util::sep(), $directory);
                $dir = end($dirParts);
                array_pop($dirParts);
                @rmdir($dir);

                if (count($dirParts)) {
                    return true;
                }

                static::rmdir(implode(Util::sep(), $dirParts));
            }
        } else {
            return @rmdir(realpath($directory));
        }

		return false;
    }

	/**
	 * Lance la configuration
	 * 
	 * @param object $config
	 */
    public static function configure($config = null)
    {
        if ($config !== null) {
            static::$fileExtension = $config->upload_file_extension;
            static::$uploadDir = $config->upload_directory;
        }
    }

	/**
	 * Ouvrie un fichier
	 * 
	 * @param string $file
	 * @param string $mod
	 * @return resource
	 */
    private static function open($file, $mod)
	{
        $rFile = fopen($file, $mod);
        return $rFile;
    }

	/**
	 * Ferme un resource fichier
	 * 
	 * @param resource $rFile
	 */
    private static function closeFile($rFile)
    {
        if (is_resource($rFile) && get_resource_type($rFile) === "file") {
            fclose($rFile);
        }
    }

	/**
	 * Lire dans le répertoire spécifier
	 * 
	 * @param string $dirname
	 * @param string $type
	 * 
	 * @return array
	 */
    private static function readIndir($dirname, $type = "file")
    {
        $files = [];
        $method = "is_file";
        $dir = opendir($dirname);
        
        if ($type == "dir") {
            $method = "is_dir";
        }
        
        while($file = readdir($dir)) {
            if ($method($file)) {
                if (!in_array($file, [".", ".."])) {
                    array_push($files, realpath($file));
                }
            }
        }

        closedir($dir);

        return $files;
    }
}