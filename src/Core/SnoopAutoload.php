<?php

/**
 * SnoopAutoload, systeme de Chargement automatique des classes.
 */
namespace System\Core;

class SnoopAutoload
{

	private static function load($class) {

		$class = str_replace("\\", "/", $class);
		$class = str_replace("System/", "", $class);

		$class = __DIR__. "/" . $class . ".php";
		echo $class;
		if (is_file($class)) {
			require $class;
		}
	}

	public static function register() {

		spl_autoload_register([__CLASS__, 'load']);

	}

}
