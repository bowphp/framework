<?php
namespace Bow\Support\Resource;

use Bow\Support\Util;
use InvalidArgumentException;
use Bow\Exception\ResourceException;

/**
 * Class Storage
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Storage
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
	 * @return mixed
	 */
	public static function store($file, $cb = null)
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
			return static::ERROR_UPLOAD_ERROR;
		}

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
		if ($file->error !== 0) {
			$status = static::ERROR_UPLOAD_ERROR;
			goto exists;
		}

		if ($file->size <= static::$fileSize) {
			$status = static::ERROR_SIZE_INVALIDE;
			goto exists;
		}

		$pathInfo = (object) pathinfo($file->name);

		if (in_array($pathInfo->extension, static::$fileExtension)) {
			$status = static::ERROR_EXTENSION_INVALIDE;
			goto exists;
		}

		if (static::$uploadFileName !== null) {
			$filename = static::$uploadFileName;
		} else {
			$filename = $pathInfo->filename;
		}

		$filename .= "." . $pathInfo->extension;

		// Déplacement du fichier tmp vers le dossier d'upload
		static::move($file->tmp_name, static::$uploadDir . "/" . $filename);

		// Status, fichier uploadé
		$status = static::SUCCESS;

		exists:
			if (is_callable($cb)) {
				call_user_func_array($cb, [$status, $filename]);
			}

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
		file_put_contents($file, $content, FILE_APPEND);
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

	/**
	 * Put
	 *
	 * @param $file
	 * @param $content
	 * @throws ResourceException
	 */
	public static function put($file, $content)
	{
		file_put_contents($file, $content);
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
		$directoryContents = glob($filename."/*");

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
	public static function directories($dirname)
	{
		$directoryContents = glob($dirname."/*");

		return array_filter($directoryContents, function($file)
		{
			return filetype($file) == "dir";
		});
	}

	/**
	 * Crée un répertoire
	 *
	 * @param string $files
	 * @param int $mode
	 * @param bool $recursive
	 * @return boolean
	 */
	public static function makeDirectory($files, $mode = 0777, $recursive = false)
	{
		if (is_bool($mode)) {
			$recursive = $mode;
			$mode = 0777;
		}

		if ($recursive === true) {
			$status = @mkdir($files, $mode, true);
		} else {
			$status = @mkdir($files, $mode);
		}

		return $status;
	}

	/**
	 * Récuper le contenu du fichier
	 *
	 * @param $filename
	 * @return null|string
	 */
	public static function get($filename)
	{
		if (is_file($filename) && stream_is_local($filename)) {
			return file_get_contents($filename);
		}

		return null;
	}

	/**
	 * Copie le contenu d'un fichier source vers un fichier cible.
	 *
	 * @param $targerFile
	 * @param $sourceFile
	 */
	public static function copy($targerFile, $sourceFile)
	{
		if (!static::exists($targerFile)) {
			throw new \RuntimeException("$targerFile n'exist pas.", E_ERROR);
		}

		if (!static::exists($sourceFile)) {
			static::makeDirectory(dirname($sourceFile), true);
		}

		file_put_contents($sourceFile, static::get($targerFile));
	}

	/**
	 * Rénomme ou déplace un fichier source vers un fichier cible.
	 *
	 * @param $targerFile
	 * @param $sourceFile
	 */
	public static function move($targerFile, $sourceFile)
	{
		static::copy($targerFile, $sourceFile);
		static::delete($targerFile);
	}

	/**
	 * Vérifie l'existance d'un fichier
	 *
	 * @param $filename
	 * @return bool
	 */
	public static function exists($filename)
	{
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
	public static function extension($filename)
	{
		if (static::exists($filename)) {
			return pathinfo($filename, PATHINFO_EXTENSION);
		}

		return null;
	}

	/**
	 * isFile aliase sur is_file.
	 *
	 * @param $filename
	 * @return bool
	 */
	public static function isFile($filename)
	{
		return is_file($filename);
	}

	/**
	 * Lance la connection au ftp.
	 *
	 * @return Ftp\FTP
	 */
	public static function ftp()
	{
		return Ftp\FTP::configure();
	}

	/**
	 * @return Ftp\FTP
	 */
	public static function disk()
	{
		return new Ftp\FTP();
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
		}
	}
}