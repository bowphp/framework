<?php



class Resource
{
	/**
	 * Liste des constantes d'erreur
	 * pour l'upload de fichier.
	 */
	const ERROR = 5;
	const SUCCESS = 7;
	const WARNING = 6;
	private static $config = [];
	private static $storageDir = null;
	// Répertoire par defaut de upload
	private static $uploadDir = "/public";
	// Taille par defaut d'un fichier
	private static $fileSize = 20000000;
	// Nom d'un fichier
	private static $uploadFileName = null;
	// Liste des extensions par defaut
	private $fileExtension = ["png", "jpg"];
    /**
	 * Modifier le nom par defaut du file uploader.
	 * @param string $filename
	 * @return self
	 */
	public static function setUploadFileName($filename)
	{
		self::$uploadFileName = $filename;
		return $this;
	}

	/**
	 * @param $extension
	 * @return $this
	 */
	public static function setFileExtension($extension)
	{
		if (is_array($extension)) {
			$this->fileExtension = $extension;
		} else {
			$this->fileExtension = func_get_args();
		}
		return $this;
	}

	/**
	 * setUploadedDir, fonction permettant de redefinir le repertoir d'upload
	 * @param string:path, le chemin du dossier de l'upload
	 * @throws \InvalidArgumentException
	 * @return \System\Snoop
	 */
	public static function setUploadDir($path)
	{
		if (is_string($path)) {
			self::$uploadDir = $path;
		} else {
			throw new \InvalidArgumentException("L'argument donnée a la fontion doit etre un entier");
		}
		return $this;
	}

	/**
	 * Modifie la taille prédéfinie de l'image a uploader.
	 * @param integer $size
	 * @throws \InvalidArgumentException
	 * @return \System\Snoop
	 */
	public static function setFileSize($size)
	{
		if (is_int($size)) {
			self::$fileSize = $size;
		} else {
			throw new \InvalidArgumentException("L'argument donnée à la fonction doit être de type entier");
		}
		return $this;
	}

	/**
	 * UploadFile, fonction permettant de uploader un fichier
	 *
	 * @param array $file information sur le fichier, $_FILES
	 * @param callable|null $cb
	 * @param string $hash=null
	 * @return \System\Snoop
	 */
	public static function uploadFile($file, $cb = null, $hash = null)
	{
		if (!is_object($file) && !is_array($file)) {
			Util::launchCallBack($cb, [new \InvalidArgumentException("Parametre invalide <pre>" . var_export($file, true) ."</pre>. Elle doit etre un tableau ou un object StdClass")]);
		}
		if (empty($file)) {
			Util::launchCallBack($cb, [new \InvalidArgumentException("Le fichier a uploader n'existe pas")]);
		}
		if (is_array($file)) {
			$file = (object) $file;
		}
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
					if (in_array($pathInfo->extension, $this->fileExtension)) {
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
		return $this;
	}

    public static function put($file, $content)
    {
        if (is_file(realpath($file))) {
            return file_get_contents(self::$storageDir."/".$file, )
        } else {

        }
    }

    public static function append($file, $content)
    {
        self::write(self::open($file, "a"), $content);
    }

    public static function preappend($file, $content)
    {
        $tmp_content = file_get_contents($file);
        self::put($file, $content);
        self::append($file, $tmp_content);
    }

    public static function delete($file)
    {
        unlink($file);
    }

    public static function files($dirname)
    {
        return self::readIndir($dirname, "file");
    }

    public static function directories($dirname)
    {
        return self::readIndir($files, "dir");
    }

    public static function makeDirectory($files, $recursive = false)
    {
        if ($recursive === true) {
            $status = @mkdir($files, 0777, true);
        } else {
            $status = @mkdir($files, 0777);
        }
    }

    public static function deleteDirectory($file, $recursive)
    {
		return null;
    }

    public static configure($config = ["resource" => "local"])
    {
        if (is_string($config)) {
            self::$storageDir = $config;
        } else {
			if ($config["resource"] == "local") {
				self::$config = require dirname(__DIR__) . "/../configuration/storage.php";
				self::$storageDir = self::$config["local"];
			}
        }
    }

    private static isConfigured()
    {
        return self::$storageDir !== null ? true : false;
    }

    private static open($file, $mod)
	{
        $rFile = fopen($file, $mod);
        return $rFile;
    }

    private static closeFile($rFile)
    {
        if (is_resource($rFile)) {
            fclose($rFile);
        }
    }

    private function readIndir($dirname, $type = "file")
    {
        $files = [];
        $method = "is_file";
        $dir = readdir($dirname);
        if ($type == "dir") {
            $method = "is_dir";
        }
        while($file = readdir($dir)) {
            if ($method($file)) {
                if (!in_array($file, [".", ".."] ) {
                    array_push($files, $file);
                }
            }
        }
        closedir($dir);
        return $files;
    }

	/**
	 * files, retourne les informations du $_FILES
	 * @param string|null $key
	 * @return mixed
	 */
	public function files($key = null)
	{
		if ($key !== null) {
			return isset($_FILES[$key]) ? (object) $_FILES[$key] : false;
		}
		return $_FILES;
	}

	/**
	 * isParamKey, vérifie si Snoop::files contient la clé définie.
	 * @param string|int $key
	 * @return mixed
	 */
	public function isFilesKey($key)
	{
		return isset($_FILES[$key]) && !empty($_FILES[$key]);
	}

	/**
	 * filesIsEmpty, vérifie si le tableau $_FILES est vide.
	 *	@return boolean
	 */
	public function filesIsEmpty()
	{
		return empty($_FILES);
	}

}
