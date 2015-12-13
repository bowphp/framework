<?php



namespace System\Util;


class Util
{
	/**
	 * buildSerialization, fonction permettant de construire des sérialisation
	 * @param string $file
	 * @param mixed $args
	 * @return string
	 */
	public static function serialization($file, $args)
	{
		# Sérialisation d'un mixed dans un fichier concerner.
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
	 * @return \DateTime
	 */
	public static function diffEntre2Date($date1, $date2)
	{
		try {
			$date_r = date_diff(date_create($date1), date_create($date2));
			if ($date_r) {
				return $date_r;
			}
		} catch (\Exception $e) {
			$this->kill($e);
		}
		return $this;
	}

	/**
	 * setTimeZone, modifie la zone horaire.
	 *
	 * @param string $zone
	 * @throws \ErrorException
	 * @return \System\Snoop
	 */
	public static function setTimeZone($zone)
	{
		if (count(explode("/", $zone)) != 2) {
			throw new \ErrorException("La definition de la zone est invalide");
		}
		date_default_timezone_set($zone);
	}

	/**
	 * Lanceur de callback
	 * @param callable $cb
	 * @param mixed @param[optional]
	 */
	public static function launchCallBack($cb, $param = null)
	{
		if (is_callable($cb)) {
			call_user_func_array($cb, is_array($param) ? $param : [$param]);
		} else {
			throw new InvalidArgumentException("accpet un callback: " .gettype($cb) . " donnee", 1);
		}
	}

	/**
	 * filter, fonction permettant de filter les données
	 *
	 * @param array $opts
	 * @param callable $cb
	 * @return array $r, collection de donnée élus après le tri.
	 */
	public function filtre($opts, $cb)
	{
		$r = [];
		foreach ($opts as $key => $value) {
			if (call_user_func_array($cb, [$value, $key])) {
				array_push($r, $value);
			}
		}
		// Retourne un tableau
		return $r;
	}

	/**
	 * convertHourToLetter, convert une heure en letter
	 * Format: HH:MM:SS
	 * @param string $hour
	 * @return string
	 */
	public function convertHourToLetter($hour)
	{
		$hourPart = explode(":", $hour);
		$heures = trim($this->convertDate($hourPart[0])) . " heure";
		$minutes = trim($this->convertDate($hourPart[1])) . " minute";
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
			$secondes =  " " . trim($this->convertDate($hourPart[2])) . " secondes";
		}
		// Retourne
		return trim(strtolower($heures . " " . $minutes . $secondes));
	}

	/**
	 * convertDateToLetter, convert une date sous forme de letter
	 * @param string $dateString
	 * @return string
	 */
	public function convertDateToLetter($dateString)
	{
		$formData = array_reverse(explode("-", $dateString));
		$r = trim($this->convertDate($formData[0])." ". $this->getMonth((int)$formData[1])) . " " . trim($this->convertDate($formData[2]));
		$p = explode(" ", $r);

		if (strtolower($p[0]) == "un") {
			$p[0] = "permier";
		}
		return trim(implode(" ", $p));
	}

}
