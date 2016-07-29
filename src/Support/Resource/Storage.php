<?php
namespace Bow\Support\Resource;

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
	public static function store($file, $location, $size, array $extension, $cb)
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

		$r = static::copy($file['tmp_name'], $location);

		return call_user_func_array($cb, [$r == true ? false : 'uploaded']);
	}

	/**
	 * Ecrire à la suite d'un fichier spécifier
	 *
	 * @param string $file nom du fichier
	 * @param string $content content a ajouter
	 * @return bool
	 */
	public static function append($file, $content)
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
	public static function prepend($file, $content)
	{
		$tmp_content = file_get_contents($file);

		static::put($file, $content);
		return static::append($file, $tmp_content);
	}

	/**
	 * Put
	 *
	 * @param $file
	 * @param $content
	 * @throws ResourceException
	 * @return bool
	 */
	public static function put($file, $content)
	{
		return file_put_contents($file, $content);
	}

	/**
	 * Supprimer un fichier
	 *
	 * @param string $file
	 * @return boolean
	 */
	public static function delete($file)
	{
		if (is_dir($file)) {
			return rmdir($file);
		}

		return unlink($file);
	}

	/**
	 * Alias sur readInDir
	 *
	 * @param string $dirname
	 * @return array
	 */
	public static function files($dirname)
	{
		$dirname = rtrim($dirname, '/');
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

		return @mkdir($files, $mode, $recursive);
	}

	/**
	 * Récuper le contenu du fichier
	 *
	 * @param string $filename
	 * @return null|string
	 */
	public static function get($filename)
	{
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
	public static function copy($targerFile, $sourceFile)
	{
		if (!static::exists($targerFile)) {
			throw new \RuntimeException("$targerFile n'exist pas.", E_ERROR);
		}

		if (!static::exists($sourceFile)) {
			static::makeDirectory(dirname($sourceFile), true);
		}

		return file_put_contents($sourceFile, static::get($targerFile));
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
	 * isDirectory aliase sur is_dir.
	 *
	 * @param $dirname
	 * @return bool
	 */
	public static function isDirectory($dirname)
	{
		return is_dir($dirname);
	}

	/**
	 * Lance la connection au ftp.
	 *
	 * @return Ftp\FTP
	 */
	public static function ftp()
	{
		return new Ftp\FTP();
	}
}