<?php

/**
 * SnoopAutoload, systeme de Chargement automatique des classes.
 */
namespace Snoop;

class ApplicationAutoload
{
	/**
	 * Charge le fichier original de la classe
	 *
	 * @param $class
	 * @return void
	 */
	private static function load($class)
	{
		$class = str_replace("\\", "/", $class);
		$class = preg_replace("~Snoop/~", "src/", $class);
		$class = dirname(__DIR__). "/" . $class . ".php";

		if (is_file($class)) {
			require $class;
		}
	}

	/**
	 * Launce l'autoload
	 *
	 * @return void
	 */
	public static function register()
	{
		spl_autoload_register([__CLASS__, 'load']);
	}

}
