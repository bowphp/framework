<?php


namespace Snoop\Support;

use InvalidArgumentException;


class Resource
{
	/**
	 * Liste des constantes d'erreur pour l'upload de fichier.
	 */
	const ERROR = 5;
	const SUCCESS = 7;
	const WARNING = 6;
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
	 * @var public
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
	 * @return self
	 */
	public static function setUploadFileName($filename)
	{
		self::$uploadFileName = $filename;
	}

	/**
	 * Modifie la liste des extensions valides
	 * 
	 * @param mixed $extension
	 */
	public static function setFileExtension($extension)
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
	public static function setUploadDir($path)
	{
		if (is_string($path)) {
		
			self::$uploadDir = $path;
		
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
	public static function setFileSize($size)
	{
		if (is_int($size)) {
		
			self::$fileSize = $size;
		
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
			
			$dirPart = explode("/", self::$uploadDir);
			$end = array_pop($dirPart);
			
			if ($end == "") {
		
				self::$uploadDir = implode(DIRECTORY_SEPARATOR, $dirPart);
		
			} else {
		
				self::$uploadDir = implode(DIRECTORY_SEPARATOR, $dirPart) .DIRECTORY_SEPARATOR. $end;
		
			}

			if (!is_dir(self::$uploadDir)) {
		
				@mkdir(self::$uploadDir, 0766);
		
			}

			// Si le fichier est bien uploader, avec aucune error
			if ($file->error === 0) {

				if ($file->size <= self::$fileSize) {
					
					$pathInfo = (object) pathinfo($file->name);
		
					if (in_array($pathInfo->extension, static::$fileExtension)) {
						
						if ($hash !== null) {
							
							if (self::$uploadFileName !== null) {
		
								$filename = hash($hash, self::$uploadFileName);
		
							} else {
		
								$filename = hash($hash, uniqid(rand(null, true)));
		
							}

						} else {
							
							if (self::$uploadFileName !== null) {
		
								$filename = self::$uploadFileName;
		
							} else {
		
								$filename = $pathInfo->filename;
		
							}

						}

						move_uploaded_file($file->tmp_name, self::$uploadDir . "/" . $filename . '.' . $pathInfo->extension);
						
						// Status, fichier uploadé
						$status = [
							"status" => self::SUCCESS,
							"message" => "File Uploaded"
						];

					} else {
						
						# Status, extension du fichier
						$status = [
							"status" => self::ERROR,
							"message" => "Availabe File, verify file type"
						];

					}

				} else {
					
					# Status, la taille est invalide
					$status = [
						"status" => self::ERROR,
						"message" => "File is more big, max size " . self::$fileSize. " octets."
					];

				}

			} else {
				
				# Status, fichier erroné.
				$status = [
					"status" => self::ERROR,
					"message" => "Le fichier possède des erreurs"
				];

			}

		} else {
			
			# Status, fichier non uploadé
			$status = [
				"status" => self::ERROR,
				"message" => "Le fichier n'a pas pus être uploader"
			];

		}

		if ($cb !== null) {
			
			call_user_func_array($cb, [(object) $status, isset($filename) ? $filename : null, isset($ext) ? $ext : null]);
		} else {

			return $status;
		}

		return null;
	}

	/**
	 * Ecrire dans le fichier spécifier
	 * 
	 * @param string $file
	 * @param string $content
	 */
    public static function put($file, $content)
    {
        if (is_file(realpath($file))) {
        
            return file_get_contents(self::$storageDir . "/" . $file, $content);
        
        }

        return null;
    }

	/**
	 * Ecrire à la suite d'un fichier spécifier
	 * 
	 * @param string $file
	 * @param string $content
	 */
    public static function append($file, $content)
    {
        self::write(self::open($file, "a"), $content);
    }

	/**
	 * Ecrire au début d'un fichier spécifier
	 * 
	 * @param string $file
	 * @param string $content
	 */
    public static function preappend($file, $content)
    {
        $tmp_content = file_get_contents($file);
        
        self::put($file, $content);
        self::append($file, $tmp_content);
    }

	/**
	 * Supprimer un fichier
	 * 
	 * @param string $file
	 * @param string $content
	 */
    public static function delete($file)
    {
        @unlink($file);
    }

	/**
	 * Alias sur readInDir
	 * 
	 * @param string $filename
	 */
    public static function files($filename)
    {
        return self::readIndir($filename, "file");
    }

	/**
	 * Alias sur readInDir
	 * 
	 * @param string $dirname
	 */
    public static function directories($dirname)
    {
        return self::readIndir($dirname, "dir");
    }

	/**
	 * Crée un répertoire
	 * 
	 * @param string $file
	 * @param bool $recursive
	 */
    public static function makeDirectory($files, $recursive = false)
    {
        if ($recursive === true) {

            $status = @mkdir($files, 0777, true);
        
        } else {
        
            $status = @mkdir($files, 0777);
        
        }
    }

	/**
	 * Supprime un répertoire.
	 * 
	 * @param string $file
	 * @param bool $recursive
	 */
    public static function deleteDirectory($file, $recursive = false)
    {
		return null;
    }

	/**
	 * Lance la configuration
	 * 
	 * @param object $config
	 */
    public static function configure($config)
    {
    	static::$fileExtension = $config->uploadFileExtension;
    	$c = $config->uploadConfiguration;

    	if ($c->type === "folder") {
    	
    		static::$uploadDir = $c->config["folder"]["dirname"];
    	
    	} else {
    	
    		// Todo: ftp workflow
    	
    	}
    }

	/**
	 * Verifie la configuration
	 * 
	 * @return bool
	 */
    private static function isConfigured()
    {
        return self::$storageDir !== null ? true : false;
    }

	/**
	 * Ouvrie un fichier
	 * 
	 * @param string $file
	 * @param string $mod
	 * 
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
        if (is_resource($rFile)) {
        
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
        $dir = readdir($dirname);
        
        if ($type == "dir") {
        
            $method = "is_dir";
        
        }
        
        while($file = readdir($dir)) {
        
            if ($method($file)) {
        
                if (!in_array($file, [".", ".."])) {
        
                    array_push($files, $file);
        
                }
        
            }
        
        }

        closedir($dir);
    
        return $files;
    }

}