<?php
namespace Bow\Support\Validate;

use Bow\Http\Request;
use Bow\Http\RequestData;

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
     * @param RequestData $inputs Les informations a validé
     * @param array $rules Le critaire de validation
     *
     * @return Validate
     */
    public static function make(RequestData $inputs, array $rules)
    {
        $isValide = true;

        foreach($rules as $key => $rule) {
            $rule = explode("|", $rule);
            if ($rule[0] == "required") {
                $isValide = $inputs->has($key);
                if (!$isValide) {
                    break;
                }
            }
        }

        return new Validate($isValide, "", []);
    }
}