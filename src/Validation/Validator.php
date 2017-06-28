<?php
namespace Bow\Validation;

use Bow\Support\Str;
use Bow\Database\Database;
use const FILTER_FLAG_EMAIL_UNICODE;
use function filter_var;
use function preg_match;
use function preg_quote;
use function str_replace;

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
     * @var bool
     */
    protected $fail = false;

    /**
     * @var string
     */
    protected $lastMessage;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $inputs = [];

    /**
     * @var array
     */
    protected $compiles = [
        'Max',
        'Min',
        'Lower',
        'Upper',
        'Size',
        'Same',
        'Alpha',
        'AlphaNum',
        'Number',
        'Email',
        'In',
        'Int',
        'Exists',
        'Unique'
    ];

    /**
     * @var array
     */
    private $lexique = [
        'email' => "Le champ :attribute doit &ecir;tre un email.",
        'required' => "Le champ :attribute est requis.",
        'empty' => "Le champ :attribute n'est pas défini dans les données à valider.",
        'min' => "Le champ :attribute doit avoir un contenu minimal de :length.",
        'max' => "Le champ :attribute doit avoir un contenu maximal de :length.",
        'same' => "Le champ :attribute doit avoir un contenu égal à :value.",
        'number' => "Le champ :attribute doit avoir un contenu en numérique.",
        'int' => "Le champ :attribute doit avoir un contenu de type entier.",
        'float' => "Le champ :attribute doit avoir un contenu de type réel.",
        'alphanum' => "Le champ :attribute doit avoir un contenu en alphanumérique.",
        'in' => "Le champ :attribute doit avoir un contenu une valeur dans :value.",
        'size' => "Le champ :attribute doit avoir un contenu de :length caractère(s).",
        'lower' => "Le champ :attribute doit avoir un contenu en miniscule.",
        'upper' => "Le champ :attribute doit avoir un contenu en majiscule.",
        'alpha' => "Le champ :attribute doit avoir un contenu en alphabetique.",
        'exists' => "le champe :attribute n'existe pas.",
        'not_exists' => "le champ :attribute existe.",
        'unique' => "le champ :attribute n'est pas unique.",
        'date' => "Le champ :attribute n'est pas une date au format yyyy-mm-dd",
        'datetime' => "Le champ :attribute n'est pas une date au format yyyy-mm-dd hh:mm:ss",
        'regex' => "Le champ :attribute n'est pas valide",
    ];

    /**
     * Tout les marqueurs possible.
     *
     * - required   Vérifie que le champ existe dans les données à valider
     * - min:value  Vérifie que le contenu du champ est un nombre de caractère minimal suivant la valeur définie
     * - max:value  Vérifie que le contenu du champ est un nombre de caractère maximal suivant la valeur définie
     * - size:value Vérifie que le contenu du champ est un nombre de caractère égale à la valeur définie
     * - eq:value   Vérifie que le contenu du champ soit égale à la valeur définie
     * - email      Vérifie que le contenu du champ soit une email
     * - number     Vérifie que le contenu du champ soit un nombre
     * - alphanum   Vérifie que le contenu du champ soit une chaine alphanumérique
     * - alpha      Vérifie que le contenu du champ soit une alpha
     * - upper      Vérifie que le contenu du champ soit une chaine en majiscule
     * - lower      Vérifie que le contenu du champ soit une chaine en miniscule
     * - in:(value, ..) Vérifie que le contenu du champ soit une parmis les valeurs définies.
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
        $v = new static();
        return $v->validate($inputs, $rules);
    }

    /**
     * @param array $inputs
     * @param array $rules
     * @return Validate
     */
    public function validate(array $inputs, array $rules)
    {
        $this->inputs = $inputs;

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
                $this->errors[$key] = [];

                // Masque sur la règle required
                if ($masque == "required") {
                    if (!isset($inputs[$key]) || is_null($inputs[$key]) || $inputs[$key] === '') {
                        $this->lastMessage = $message = $this->lexique('required', $key);
                        $this->errors[$key][] = ["masque" => $masque, "message" => $message];
                        $this->fail = true;
                    }
                } else {
                    if (!isset($inputs[$key])) {
                        $this->lastMessage = $message = $this->lexique('empty', $key);
                        $this->errors[$key][] = ["masque" => $masque, "message" => $message];
                        $this->fail = true;
                        continue;
                    }
                }

                foreach ($this->compiles as $compile) {
                    $this->{'compile'.$compile}($key, $masque);
                }

                // On nettoye la lsite des erreurs si la clé est valide
                if (empty($this->errors[$key])) {
                    unset($this->errors[$key]);
                }
            }
        }

        return new Validate($this->fail, $this->lastMessage, $this->errors);
    }

    /**
     * @param string $key
     * @param string|array $attributes
     * @return mixed
     */
    private function lexique($key, $attributes)
    {
        if (is_string($attributes)){
            $attributes = ['attribute' => $attributes];
        }

        $lexique = $this->lexique[$key];

        foreach ($attributes as $key => $value) {
            $lexique = str_replace(':'.$key, $value, $lexique);
        }

        return $lexique;
    }

    /**
     * Masque sur la règle min
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileMin($key, $masque)
    {
        if (preg_match("/^min:(\d+)$/", $masque, $match)) {
            $length = (int) end($match);
            if (Str::len($this->inputs[$key]) < $length) {
                $this->lastMessage = $this->lexique('min', [
                    'attribute' => $key,
                    'length' => $length
                ]);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle max
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileMax($key, $masque)
    {
        if (preg_match("/^max:(\d+)$/", $masque, $match)) {
            $length = (int) end($match);
            if (Str::len($this->inputs[$key]) > $length) {
                $this->lastMessage = $this->lexique('max', [
                    'attribute' => $key,
                    'length' => $length
                ]);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle same
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileSame($key, $masque)
    {
        if (preg_match("/^same:(.+)$/", $masque, $match)) {
            $value = (string) end($match);
            if ($this->inputs[$key] != $value) {
                $this->lastMessage = $this->lexique('same', [
                    'attribute' => $key,
                    'value' => $value
                ]);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle email.
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileEmail($key, $masque)
    {
        if (preg_match("/^email$/", $masque, $match)) {
            if (!Str::isMail($this->inputs[$key])) {
                $this->lastMessage = $this->lexique('email', $key);
                $errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle number
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileNumber($key, $masque)
    {
        if (preg_match("/^number$/", $masque, $match)) {
            if (!is_numeric($this->inputs[$key])) {
                $this->lastMessage = $this->lexique('number', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle int
     *
     * @param $key
     * @param $masque
     */
    protected function compileInt($key, $masque)
    {
        if (preg_match("/^int$/", $masque, $match)) {
            if (!is_int($this->inputs[$key])) {
                $this->lastMessage = $this->lexique('int', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle float$
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileFloat($key, $masque)
    {
        if (preg_match("/^float$/", $masque, $match)) {
            if (!is_float($this->inputs[$key])) {
                $this->lastMessage = $this->lexique('float', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle alphanum
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileAlphaNum($key, $masque)
    {
        if (preg_match("/^alphanum$/", $masque)) {
            if (!Str::isAlphaNum($this->inputs[$key])) {
                $this->lastMessage = $this->lexique('alphanum', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle in
     *
     * @param $key
     * @param $masque
     */
    protected function compileIn($key, $masque)
    {
        if (preg_match("/^in:(.+)$/", $masque, $match)) {
            $values = explode(",", end($match));

            foreach($values as $index => $value) {
                $values[$index] = trim($value);
            }

            if (!in_array($this->inputs[$key], $values)) {
                $this->lastMessage = $this->lexique('in', [
                    'attribute' => $key,
                    'value' => implode(", ", $values)
                ]);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle size
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileSize($key, $masque)
    {
        if (preg_match("/^size:(\d+)$/", $masque, $match)) {
            $length = (int) end($match);
            if (Str::len($this->inputs[$key]) != $length) {
                $this->lastMessage = $this->lexique('size', [
                    'attribute' => $key,
                    'length' => $length
                ]);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle lower
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileLower($key, $masque)
    {
        if (preg_match("/^lower/", $masque)) {
            if (!Str::isLower($this->inputs[$key])) {
                $this->lastMessage = $this->lexique('lower', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle upper
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileUpper($key, $masque)
    {
        if (preg_match("/^upper/", $masque)) {
            if (!Str::isUpper($this->inputs[$key])) {
                $this->lastMessage = $this->lexique('upper', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle alpha
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileAlpha($key, $masque)
    {
        if (preg_match("/^alpha$/", $masque)) {
            if (!Str::isAlpha($this->inputs[$key])) {
                $this->lastMessage = $this->lexique('alpha', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle alpha
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileExists($key, $masque)
    {
        if (preg_match("/^exists:(.+)$/", $masque, $match)) {
            $catch = end($match);
            $parts = explode(',', $catch);

            if (count($parts) == 1) {
                $exists = Database::table($parts[0])->where($key, $this->inputs[$key])->exists();
            } else {
                $exists = Database::table($parts[0])->where($parts[1], $this->inputs[$key])->exists();
            }

            if (!$exists) {
                $this->lastMessage = $this->lexique('exists', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle alpha
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileNotExists($key, $masque)
    {
        if (preg_match("/^!exists:(.+)$/", $masque, $match)) {
            $catch = end($match);
            $parts = explode(',', $catch);

            if (count($parts) == 1) {
                $exists = Database::table($parts[0])->where($key, $this->inputs[$key])->exists();
            } else {
                $exists = Database::table($parts[0])->where($parts[1], $this->inputs[$key])->exists();
            }

            if ($exists) {
                $this->lastMessage = $this->lexique('not_exists', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle alpha
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileUnique($key, $masque)
    {
        if (preg_match("/^unique:(.+)$/", $masque, $match)) {
            $catch = end($match);
            $parts = explode(',', $catch);

            if (count($parts) == 1) {
                $count = Database::table($parts[0])->where($key, $this->inputs[$key])->count();
            } else {
                $count = Database::table($parts[0])->where($parts[1], $this->inputs[$key])->count();
            }

            if ($count >= 1) {
                $this->lastMessage = $this->lexique('exists', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle alpha
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileDate($key, $masque)
    {
        if (preg_match("/^date$/", $masque, $match)) {
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->inputs[$key])) {
                $this->lastMessage = $this->lexique('date', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle alpha
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileDateTime($key, $masque)
    {
        if (preg_match("/^datetime$/", $masque, $match)) {
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/z', $this->inputs[$key])) {
                $this->lastMessage = $this->lexique('datetime', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }

    /**
     * Masque sur la règle alpha
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileRegex($key, $masque)
    {
        if (preg_match("/^regex:(.+)+$/", $masque, $match)) {
            $regex = '/'.preg_quote($match[1]).'/';
            if (preg_match($regex, $this->inputs[$key])) {
                $this->lastMessage = $this->lexique('regex', $key);
                $this->errors[$key][] = ["masque" => $masque, "message" => $this->lastMessage];
                $this->fail = true;
            }
        }
    }
}