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
            // Formatage de la régle
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

                if (preg_match("/^min:(\d+)$/", $masque, $match)) {
                    $length = (int) end($match);
                    if (Str::len($inputs[$key]) < $length) {
                        $message = "Le champs \"$key\" doit avoir un contenu minimal de $length.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                if (preg_match("/^max:(\d+)$/", $masque, $match)) {
                    $length = (int) end($match);
                    if (Str::len($inputs[$key]) > $length) {
                        $message = "Le champs \"$key\" doit avoir un contenu maximal de $length.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                if (preg_match("/^email$/", $masque, $match)) {
                    if (!Str::isMail($inputs[$key])) {
                        $message = "Le champs $key doit avoir un contenu au format email.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                if (preg_match("/^number$/", $masque, $match)) {
                    if (!is_numeric($inputs[$key])) {
                        $message = "Le champs \"$key\" doit avoir un contenu en numérique.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                    } else {
                        $isFails = true;
                    }
                }

                if (preg_match("/^alphanum$/", $masque)) {
                    if (!Str::isAlphaNum($inputs[$key])) {
                        $message = "Le champs \"$key\" doit avoir un contenu en alphanumérique.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                if (preg_match("/^alpha$/", $masque)) {
                    if (!Str::isAlpha($inputs[$key])) {
                        $message = "Le champs \"$key\" doit avoir un contenu en alphabetique.";
                        $errors[$key][] = ["masque" => $masque, "message" => $message];
                        $isFails = true;
                    }
                }

                if (empty($errors[$key])) {
                    unset($errors[$key]);
                }

                if (!$isFails) {
                    break;
                }
            }
        }

        return new Validate($isFails, $message, $errors);
    }
}