<?php


class Convertor
{

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

		$tensOut = "";
		$centsOut = "";
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
}
