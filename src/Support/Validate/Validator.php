<?php
namespace Bow\Support\Validate;

use Bow\Support\Str;

/**
 * Class Validator
 *
 * C'est un validateur minimaliste.
 *
 * @package Bow\Support
 */
class Validator
{
    /**
     * Tout les marqueurs possible.
     *
     * - required   Vérifie que le champs existe dans les données à valider
     * - min:value  Vérifie que le contenu du champs est un nombre de caractère minimal suivant la valeur définie
     * - max:value  Vérifie que le contenu du champs est un nombre de caractère maximal suivant la valeur définie
     * - size:value Vérifie que le contenu du champs est un nombre de caractère égale à la valeur définie
     * - eq:value   Vérifie que le contenu du champs soit égale à la valeur définie
     * - email      Vérifie que le contenu du champs soit une email
     * - number     Vérifie que le contenu du champs soit un nombre
     * - alphanum   Vérifie que le contenu du champs soit une chaine alphanumérique
     * - alpha      Vérifie que le contenu du champs soit une alpha
     * - upper      Vérifie que le contenu du champs soit une chaine en majiscule
     * - lower      Vérifie que le contenu du champs soit une chaine en miniscule
     * - in:(value, ..) Vérifie que le contenu du champs soit une parmis les valeurs définies.
     *
     * e.g: required|max:255
     *      required|email|min:49
     *      required|confirmed
     *
     * @param array $inputs Les informations a validé
     * @param array $rules Le critaire de validation
     *
     * @return Validate
     */
    public static function make(array $inputs, array $rules)
    {
        $isFails = false;
        $errors = [];
        $message = "";

        foreach($rules as $key => $rule) {
            /**
             * Formatage et validation de chaque règle
             * eg. name => "required|max:100|alpha"
             */
            foreach(explode("|", $rule) as $masque) {
                // Dans le case il y a un | superflux.
                if (is_int($masque) || Str::len($masque) == "") {
                    continue;
                }

                // Erreur listes.
                $errors[$key] = [];

                // Masque sur la règle required
                if ($masque == "required") {
                    if (!isset($inputs[$key])) {
                        $message = "Le champs \"$key\" est requis.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                } else {
                    if (!isset($inputs[$key])) {
                        $message = "Le champs \"$key\" n'est pas défini dans les données à valider.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                        continue;
                    }
                }

                // Masque sur la règle min
                if (preg_match("/^min:(\d+)$/", $masque, $match)) {
                    $length = (int) end($match);
                    if (Str::len($inputs[$key]) < $length) {
                        $message = "Le champs \"$key\" doit avoir un contenu minimal de $length.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle max
                if (preg_match("/^max:(\d+)$/", $masque, $match)) {
                    $length = (int) end($match);
                    if (Str::len($inputs[$key]) > $length) {
                        $message = "Le champs \"$key\" doit avoir un contenu maximal de $length.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle size
                if (preg_match("/^size:(\d+)$/", $masque, $match)) {
                    $length = (int) end($match);
                    if (Str::len($inputs[$key]) == $length) {
                        $message = "Le champs \"$key\" doit avoir un contenu de $length caractère(s).";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle in
                if (preg_match("/^in:\((.+)\)$/", $masque, $match)) {
                    $values = explode(",", end($match));
                    foreach($values as $index => $value) {
                        $values[$index] = trim($value);
                    }

                    if (!in_array($inputs[$key], $values)) {
                        $message = "Le champs \"$key\" doit avoir un contenu une valeur dans " . implode(", ", $values) . ".";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle eq
                if (preg_match("/^eq:(.+)$/", $masque, $match)) {
                    $value = (string) end($match);
                    if ($inputs[$key] == $value) {
                        $message = "Le champs \"$key\" doit avoir un contenu égal à '$value'.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle email.
                if (preg_match("/^email$/", $masque, $match)) {
                    if (!Str::isMail($inputs[$key])) {
                        $message = "Le champs $key doit avoir un contenu au format email.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle number
                if (preg_match("/^number$/", $masque, $match)) {
                    if (!is_numeric($inputs[$key])) {
                        $message = "Le champs \"$key\" doit avoir un contenu en numérique.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle alphanum
                if (preg_match("/^alphanum$/", $masque)) {
                    if (!Str::isAlphaNum($inputs[$key])) {
                        $message = "Le champs \"$key\" doit avoir un contenu en alphanumérique.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle upper
                if (preg_match("/^upper/", $masque)) {
                    if (!Str::isUpper($inputs[$key])) {
                        $message = "Le champs \"$key\" doit avoir un contenu en majiscule.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle lower
                if (preg_match("/^lower/", $masque)) {
                    if (!Str::isLower($inputs[$key])) {
                        $message = "Le champs \"$key\" doit avoir un contenu en miniscule.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // Masque sur la règle alpha
                if (preg_match("/^alpha$/", $masque)) {
                    if (!Str::isAlpha($inputs[$key])) {
                        $message = "Le champs \"$key\" doit avoir un contenu en alphabetique.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                // On nettoye la lsite des erreurs si la clé est valide
                if (empty($errors[$key])) {
                    unset($errors[$key]);
                }
            }
        }

        return new Validate($isFails, $message, $errors);
    }
}