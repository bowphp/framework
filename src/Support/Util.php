<?php


namespace System\Support;

use DateTime;
use ErrorException;
use InvalidArgumentException;

class Util
{
	/**
	 * definir le type de retoure chariot CRLF ou LF
	 *
	 * @var string
	 */
	private static $sep;
	/**
	 * Configuration de date en francais.
	 */
	private static $angMounth = [
		"Jan"  => "Jan", "Fév"  => "Feb",
		"Mars" => "Mar", "Avr"  => "Apr",
		"Mai"  => "Mai", "Juin" => "Jun",
		"Juil" => "Jul", "Août" => "Aug",
		"Sept" => "Sep", "Oct"  => "Oct",
		"Nov"  => "Nov", "Déc"  => "Dec"
	];
	private static $month = [
		"Jan"  => "Janvier", "Fév"  => "Fevrier",
		"Mars" => "Mars", "Avr"  => "Avril",
		"Mai"  => "Mai", "Juin" => "Juin",
		"Juil" => "Juillet", "Août" => "Août",
		"Sept" => "Septembre", "Oct" => "Octobre",
		"Nov"  => "Novembre", "Déc" => "Décembre"
	];
	/**
	 * buildSerialization, fonction permettant de construire des sérialisation
	 * 
	 * @param string $file
	 * @param mixed $args
	 * @return string
	 */
	public static function serialization($file, $args)
	{
		// Sérialisation d'un mixed dans un fichier concerner.
		return (bool) @file_put_contents($file, serialize($args));
	}

	/**
	 * UnBuildSerializationVariable, fonction permettant de récrier la variable sérialiser
	 *
	 * @param string $filePath
	 * @return mixed
	 */
	public static function disSerialization($filePath)
	{
		// Ouverture du fichier de sérialisation.
		$serializedData = @file_get_contents($filePath);
		if (is_string($serializedData)) {
			// On retourne l'element dé-sérialisé
			return unserialize($serializedData);
		}
		return $serializedData;
	}

	/**
	 * difference entre deux date
	 *
	 * @param string $datenaiss
	 * @param boolean $age
	 * @return array
	 */
	public static function dateDiff($datenaiss, $age = false)
	{
		$date1 = date_create();
		$date2 = date_create($datenaiss);

		if ($date1 !== false && $date2 !== false) {
			$diff = date_diff($date1, $date2);
			if ($diff->format("%R") === "-") {
				if ($age === true) {
					return $diff->y;
				}
				$error = true;
			} else {
				$error = true;
			}
		} else {
			$error = true;
		}
		return $error;
	}

	/**
	 * diffEntre2Date, faire la difference entre deux dates
	 *
	 * @param $date1
	 * @param $date2
	 * @return DateTime|void
	 */
	public static function diffEntre2Date($date1, $date2)
	{
		$date_r = date_diff(date_create($date1), date_create($date2));
		return $date_r;
	}

	/**
	 * setTimeZone, modifie la zone horaire.
	 *
	 * @param string $zone
	 * @throws \ErrorException
	 */
	public static function setTimeZone($zone)
	{
		if (count(explode("/", $zone)) != 2) {
			throw new ErrorException("La definition de la zone est invalide");
		}
		date_default_timezone_set($zone);
	}

	/**
	 * Lanceur de callback
	 *
	 * @param callable $cb
	 * @param mixed @param[optional]
	 * @return mixed
	 */
	public static function launchCallBack($cb, $param = null)
	{
		if (is_callable($cb)) {
			return call_user_func_array($cb, is_array($param) ? $param : [$param]);
		}
		return null;
	}

	/**
	 * filter, fonction permettant de filter les données
	 *
	 * @param array $opts
	 * @param callable $cb
	 * @return array $r, collection de donnée élus après le tri.
	 */
	public static function filtre($opts, $cb)
	{
		$r = [];
		foreach ($opts as $key => $value) {
			if (call_user_func_array($cb, [$value, $key])) {
				array_push($r, $value);
			}
		}
		return $r;
	}

	/**
	 * convertHourToLetter, convert une heure en letter
	 * Format: HH:MM:SS
	 * 
	 * @param string $hour
	 * @return string
	 */
	public static function convertHourToLetter($hour)
	{
		$hourPart = explode(":", $hour);
		$heures = trim(static::convertDate($hourPart[0])) . " heure";
		$minutes = trim(static::convertDate($hourPart[1])) . " minute";
		$secondes = "";
		// accord des heures.
		if ($hourPart[0] > 1) {
			$heures .= "s";
		}
		// accord des minutes
		if ($hourPart[1] > 1) {
			$minutes .= "s";
		}
		// Ajout de secondes
		if (isset($hourPart[2]) && $hourPart[2] > 0) {
			$secondes =  " " . trim(static::convertDate($hourPart[2])) . " secondes";
		}
		// Retourne
		return trim(strtolower($heures . " " . $minutes . $secondes));
	}

	/**
	 * convertDateToLetter, convert une date sous forme de letter
	 * 
	 * @param string $dateString
	 * @return string
	 */
	public static function convertDateToLetter($dateString)
	{
		$formData = array_reverse(explode("-", $dateString));
		$r = trim(static::convertDate($formData[0])." ". static::getMonth((int)$formData[1])) . " " . trim(static::convertDate($formData[2]));
		$p = explode(" ", $r);
		if (strtolower($p[0]) == "un") {
			$p[0] = "permier";
		}
		return trim(implode(" ", $p));
	}

	/**
	 * Lance un var_dump sur les variables passées en parametre.
	 * 
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public static function debug()
	{
		if (func_num_args() == 0) {
			throw new InvalidArgumentException("Vous devez donner un paramtre à la function", 1);
		}
		$arr = func_get_args();
		ob_start();
		foreach ($arr as $key => $value) {
			var_dump($value);
		}
		$content = ob_get_clean();
		$content = preg_replace("~\s?\{\n\s?\}~i", " is empty", $content);
		$content = preg_replace("~(string|int|object|stdclass|bool|double|float|array)~i", "<span style=\"color: rgba(255, 0, 0, 0.5); font-style: italic\">&lt;$1&gt;</span>", $content);
		$content = preg_replace('~\((\d+)\)~im', "<span style=\"color: #498\">(len=$1)</span>", $content);
		$content = preg_replace('~\s(".+")~im', "<span style=\"color: #458\"> value($1)</span>", $content);
		$content = preg_replace("~(=>)(\n\s+?)+~im", "<span style=\"color: #754\"> is</span>", $content);
		$content = preg_replace("~(is</span>)\s+~im", "$1 ", $content);
		$content = preg_replace("~\[(.+)\]~im", "<span style=\"color:#666\"><span style=\"color: red\">key:</span>$1<span style=\"color: red\"></span></span>", $content);
		$content = "<pre><tt><div style=\"font-family: monaco, courier; font-size: 13px\">$content</div></tt></pre>";
		echo $content;
	}

	/**
	 * systeme de débugage avec message d'info
	 * 
	 * @param string $message
	 * @param callable $cb=null
	 * @return void
	 */
	public static function it($message, $cb = null)
	{
		echo "<h2>{$message}</h2>";
		if (is_callable($cb)) {
			call_user_func_array($cb, [self::class]);
		} else {
			self::debug(array_slice(func_get_args(), 1, func_num_args()));
		}
	}
	
	/**
	 * Permettant de convertie des chiffres en letter
	 * @param string $nombre
	 * @return string
	 */
	public static function convertDate($nombre)
	{
		$nombre = (int) $nombre;
		if ($nombre === 0) {
			return "zéro";
		}
		/**
		 * Definition des elements de convertion.
		 */
		$nombreEnLettre = [
			"unite" => [
				null, "un", "deux", "trois", "quatre",
				"cinq", "six", "sept", "huit", "neuf",
				"dix", "onze", "douze", "treize", "quartorze",
				"quinze", "seize", "dix-sept", "dix-huit", "dix-neuf"
			],
			"ten" => [
				null, "dix", "vingt", "trente", "quarente", "cinquante",
				"soixante", "soixante",  "quatre-vingt", "quatre-vingt"
			]
		];
		/**
		 * Calcule des:
		 * - Unité
		 * - Dixaine
		 * - Centaine
		 * - Millieme
		 */
		$unite = $nombre % 10;
		$dixaine = ($nombre % 100 - $unite) / 10;
		$cent = ($nombre % 1000 - $nombre % 100) / 100;
		$millieme = ($nombre % 10000 - $nombre % 1000) / 1000;
		/**
		 * Calcule des unites
		 */
		$unitsOut = ($unite === 1 && $dixaine > 0 && $dixaine !== 8 ? 'et-' : '') . $nombreEnLettre['unite'][$unite];

		/**
		 * Calcule des dixaines
		 */
		if ($dixaine === 1 && $unite > 0) {
			$tensOut = $nombreEnLettre["unite"][10 + $unite];
			$unitsOut = "";
		} else if ($dixaine === 7 || $dixaine === 9) {
			$tensOut = $nombreEnLettre["ten"][$dixaine] . '-' . ($dixaine === 7 && $unite === 1 ? "et-" : "") . $nombreEnLettre["unite"][10 + $unite];
			$unitsOut = "";
		} else {
			$tensOut = $nombreEnLettre["ten"][$dixaine];
		}
		/**
		 * Calcule des cemtaines
		 */
		$tensOut .= ($unite === 0 && $dixaine === 8 ? "s": "");
		$centsOut = ($cent > 1 ? $nombreEnLettre["unite"][(int)$cent].' ' : '').($cent > 0 ? 'cent' : '').($cent > 1 && $dixaine == 0 && $unite == 0 ? '' : '');
		$tmp = $centsOut.($centsOut && $tensOut ? ' ': '').$tensOut.(($centsOut && $unitsOut) || ($tensOut && $unitsOut) ? '-': '').$unitsOut;
		/**
		 * Retourne avec les millieme associer.
		 */
		return ($millieme === 1 ? "mil":($millieme > 1 ? $nombreEnLettre["unite"][(int) $millieme]." mil" : "")).($millieme ? " ".$tmp : $tmp);
	}

	/**
	 * makothereSimpleValideDate
	 *
	 * @param string $str
	 * @return string
	 */
	public function makeSimpleValideDate($str)
	{
		$mount = explode(" ", $str);
		$str = $mount[0] . " " . static::$angMounth[$mount[1]] . " " . $mount[2];
		return date("Y-m-d", strtotime($str));
	}

	/**
	 * permettant de convertir mois en lettre.
	 *
	 * @param  string | integer $value
	 * @return string|null
	 */
	public static function getMonth($value)
	{
		if (!empty($value)) {
			if (is_string($value)) {
				// définition du tableau composants les mois  avec key en string
				if (strlen($value) == 3) {
					$value = ucfirst($value);
					$month = static::$month;
				} else {
					return null;
				}
			} else {
				$value = (int) $value;
				// définition du tableau composants les mois
				if ($value > 0 && $value <= 12) {
					$value -= 1;
				} else {
					return null;
				}
				$month = array_values(static::$month);
			}
			return $month[$value];
		}
		return null;
	}

	/**
	 * Formateur de donnee. key => :value
	 *
	 * @param array $data
	 * @return array $resultat
	 */
	public function add2points(array $data)
	{
		$resultat = [];
		foreach ($data as $key => $value) {
			$resultat[$value] = ":$value";
		}
		return $resultat;
	}

	/**
	 * sep, séparateur \r\n or \n
	 *
	 * @return string
	 */
	public static function sep()
	{
		if (static::$sep !== null) {
			return static::$sep;
		}
		if (defined('PHP_EOL')) {
			static::$sep = PHP_EOL;
		} else {
			static::$sep = (strpos(PHP_OS, 'WIN') === false) ? "\n" : "\r\n";
		}
		return static::$sep;
	}

}
