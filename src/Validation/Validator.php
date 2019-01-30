<?php

namespace Bow\Validation;

use Bow\Database\Database;
use Bow\Support\Str;

class Validator
{
    /**
     * The Fails flag
     *
     * @var bool
     */
    protected $fails = false;

    /**
     * The last name
     *
     * @var string
     */
    protected $last_message;

    /**
     * The errors list
     *
     * @var array
     */
    protected $errors = [];

    /**
     * The validation DATA
     *
     * @var array
     */
    protected $inputs = [];

    /**
     * The compile Rule
     *
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
     * The lexique
     *
     * @var array
     */
    private $lexique;

    /**
     * Validator constructor
     */
    public function __construct()
    {
        $this->lexique = require __DIR__.'/stubs/lexique.php';
    }

    /**
     * Any possible markers.
     *
     * @param array $inputs
     * @param array $rules
     *
     * @return Validate
     */
    public static function make(array $inputs, array $rules)
    {
        $v = new static();

        return $v->validate($inputs, $rules);
    }

    /**
     * Make validation
     *
     * @param array $inputs
     * @param array $rules
     *
     * @return Validate
     */
    public function validate(array $inputs, array $rules)
    {
        $this->inputs = $inputs;

        foreach ($rules as $key => $rule) {
            /**
             * Formatting and validation of each rule
             * eg. name => "required|max:100|alpha"
             */
            foreach (explode("|", $rule) as $masque) {
                // In the box there is a | superflux.
                if (is_int($masque) || Str::len($masque) == "") {
                    continue;
                }

                // Error lists.
                $this->errors[$key] = [];

                // Mask on the required rule
                foreach ($this->compiles as $compile) {
                    $this->{'compile'.$compile}($key, $masque);
                }

                // We clean the list of errors if the key is valid
                if (empty($this->errors[$key])) {
                    unset($this->errors[$key]);
                }
            }
        }

        return new Validate(
            $this->fails,
            $this->last_message,
            $this->errors
        );
    }

    /**
     * Get error debuging information
     *
     * @param string       $key
     * @param string|array $attributes
     *
     * @return mixed
     */
    private function lexique($key, $attributes)
    {
        if (is_string($attributes)) {
            $attributes = ['attribute' => $attributes];
        }

        // Get lexique provider by application part
        $lexique = trans($key, $attributes);

        if (is_null($lexique)) {
            $lexique = $this->lexique[$key];

            foreach ($attributes as $key => $value) {
                $lexique = str_replace(':'.$key, $value, $lexique);
            }
        }

        return $lexique;
    }

    /**
     * Compile Required Rule
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileRequired($key, $masque)
    {
        if (!isset($this->inputs[$key])
            || is_null($this->inputs[$key])
            || $this->inputs[$key] === ''
        ) {
            $this->last_message = $message = $this->lexique('required', $key);
            
            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $message
            ];
            
            $this->fails = true;
        }
    }

    /**
     * Compile Empty Rule
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileEmpty($key, $masque)
    {
        if (!isset($this->inputs[$key])) {
            $this->fails = true;

            $this->last_message = $message = $this->lexique('empty', $key);

            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $message
            ];
        }
    }

    /**
     * Complie Min Mask
     *
     * [min:value] Check that the content of the field is a number of
     * minimal character following the defined value
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileMin($key, $masque)
    {
        if (!preg_match("/^min:(\d+)$/", $masque, $match)) {
            return;
        }

        $length = (int) end($match);

        if (Str::len($this->inputs[$key]) > $length) {
            return;
        }

        $this->fails = true;
        
        $this->last_message = $this->lexique('min', [
            'attribute' => $key,
            'length' => $length
        ]);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Max Rule
     *
     * [max:value] Check that the content of the field is a number of
     * maximum character following the defined value
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileMax($key, $masque)
    {
        if (!preg_match("/^max:(\d+)$/", $masque, $match)) {
            return;
        }

        $length = (int) end($match);

        if (Str::len($this->inputs[$key]) <= $length) {
            return;
        }

        $this->fails = true;
        
        $this->last_message = $this->lexique('max', [
            'attribute' => $key,
            'length' => $length
        ]);
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Some Rule
     *
     * [same:value] Check that the field contents are equal to the mask value
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileSame($key, $masque)
    {
        if (!preg_match("/^same:(.+)$/", $masque, $match)) {
            return;
        }

        $value = (string) end($match);

        if ($this->inputs[$key] == $value) {
            return;
        }

        $this->last_message = $this->lexique('same', [
            'attribute' => $key,
            'value' => $value
        ]);

        $this->fails = true;
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Email Rule
     *
     * [email] Check that the content of the field is an email
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileEmail($key, $masque)
    {
        if (!preg_match("/^email$/", $masque, $match)) {
            return;
        }

        if (Str::isMail($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexique('email', $key);

        $this->fails = true;
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Number Rule
     *
     * [number] Check that the contents of the field is a number
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileNumber($key, $masque)
    {
        if (!preg_match("/^number$/", $masque, $match)) {
            return;
        }

        if (is_numeric($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexique('number', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Complie Int Rule
     *
     * [int] Check that the contents of the field is an integer number
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileInt($key, $masque)
    {
        if (!preg_match("/^int$/", $masque, $match)) {
            return;
        }

        if (is_int($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexique('int', $key);
        
        $this->fails = true;
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Complie Float Rule
     *
     * [float] Check that the field content is a float number
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileFloat($key, $masque)
    {
        if (!preg_match("/^float$/", $masque, $match)) {
            return;
        }

        if (is_float($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexique('float', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Alphanum Rule
     *
     * [alphanum] Check that the field content is an alphanumeric string
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileAlphaNum($key, $masque)
    {
        if (!preg_match("/^alphanum$/", $masque)) {
            return;
        }

        if (Str::isAlphaNum($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexique('alphanum', $key);
        
        $this->fails = true;
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Complie In Rule
     *
     * [in:(value, ...)] Check that the contents of the field are equal to the defined value
     *
     * @param $key
     * @param $masque
     */
    protected function compileIn($key, $masque)
    {
        if (!preg_match("/^in:(.+)$/", $masque, $match)) {
            return;
        }

        $values = explode(",", end($match));

        foreach ($values as $index => $value) {
            $values[$index] = trim($value);
        }

        if (in_array($this->inputs[$key], $values)) {
            return;
        }

        $this->last_message = $this->lexique('in', [
            'attribute' => $key,
            'value' => implode(", ", $values)
        ]);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Size Rule
     *
     * [size:value] Check that the contents of the field is a number
     * of character equal to the defined value
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileSize($key, $masque)
    {
        if (!preg_match("/^size:(\d+)$/", $masque, $match)) {
            return;
        }

        $length = (int) end($match);
    
        if (Str::len($this->inputs[$key]) == $length) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexique('size', [
            'attribute' => $key,
            'length' => $length
        ]);
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Lower Rule
     *
     * [lower] Check that the content of the field is a string in miniscule
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileLower($key, $masque)
    {
        if (!preg_match("/^lower/", $masque)) {
            return;
        }

        if (Str::isLower($this->inputs[$key])) {
            return;
        }

        $this->fails = true;
        
        $this->last_message = $this->lexique('lower', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Upper Rule
     *
     * [upper] Check that the contents of the field is a string in uppercase
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileUpper($key, $masque)
    {
        if (!preg_match("/^upper/", $masque)) {
            return;
        }

        if (Str::isUpper($this->inputs[$key])) {
            return;
        }

        $this->fails = true;
        
        $this->last_message = $this->lexique('upper', $key);
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Alpha Rule
     *
     * [alpha] Check that the field content is an alpha
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileAlpha($key, $masque)
    {
        if (!preg_match("/^alpha$/", $masque)) {
            return;
        }

        if (Str::isAlpha($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexique('alpha', $key);
        
        $this->fails = true;
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Exists Rule
     *
     * [exists:column,table] Check that the contents of a table field exist
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileExists($key, $masque)
    {
        if (!preg_match("/^exists:(.+)$/", $masque, $match)) {
            return;
        }

        $catch = end($match);
        $parts = explode(',', $catch);

        if (count($parts) == 1) {
            $exists = Database::table($parts[0])
                ->where($key, $this->inputs[$key])->exists();
        } else {
            $exists = Database::table($parts[0])
                ->where($parts[1], $this->inputs[$key])->exists();
        }

        if (!$exists) {
            $this->last_message = $this->lexique('exists', $key);
            
            $this->fails = true;
            
            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $this->last_message
            ];
        }
    }

    /**
     * Compile Not Exists Rule
     *
     * [!exists:column,table] Checks that the contents of the field of a table do not exist
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileNotExists($key, $masque)
    {
        if (!preg_match("/^!exists:(.+)$/", $masque, $match)) {
            return;
        }

        $catch = end($match);
        $parts = explode(',', $catch);

        if (count($parts) == 1) {
            $exists = Database::table($parts[0])
                ->where($key, $this->inputs[$key])->exists();
        } else {
            $exists = Database::table($parts[0])
                ->where($parts[1], $this->inputs[$key])->exists();
        }

        if ($exists) {
            $this->last_message = $this->lexique('not_exists', $key);
            
            $this->fails = true;
            
            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $this->last_message
            ];
        }
    }

    /**
     * Compile Unique Rule
     *
     * [unique:column,table] Check that the contents of the field of a table is a single value
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileUnique($key, $masque)
    {
        if (!preg_match("/^unique:(.+)$/", $masque, $match)) {
            return;
        }

        $catch = end($match);
        $parts = explode(',', $catch);

        if (count($parts) == 1) {
            $count = Database::table($parts[0])
                ->where($key, $this->inputs[$key])->count();
        } else {
            $count = Database::table($parts[0])
                ->where($parts[1], $this->inputs[$key])->count();
        }

        if ($count >= 1) {
            $this->last_message = $this->lexique('exists', $key);
            
            $this->fails = true;
            
            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $this->last_message
            ];
        }
    }

    /**
     * Compile Date Rule
     *
     * [date] Check that the field's content is a valid date
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileDate($key, $masque)
    {
        if (!preg_match("/^date?$/", $masque, $match)) {
            return;
        }

        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->inputs[$key])) {
            return;
        }

        $this->fails = true;
        
        $this->last_message = $this->lexique('date', $key);
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Date Time Rule
     *
     * [datetime] Check that the contents of the field is a valid date time
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileDateTime($key, $masque)
    {
        if (!preg_match("/^datetime$/", $masque, $match)) {
            return;
        }

        if (!preg_match(
            '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/z',
            $this->inputs[$key]
        )) {
            return;
        }

        $this->last_message = $this->lexique('datetime', $key);
        
        $this->fails = true;
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Regex Rule
     *
     * [regex] Check that the contents of the field with a regular expression
     *
     * @param string $key
     * @param string $masque
     */
    protected function compileRegex($key, $masque)
    {
        if (!preg_match("/^regex:(.+)+$/", $masque, $match)) {
            return;
        }

        $regex = '/'.preg_quote($match[1]).'/';

        if (!preg_match($regex, $this->inputs[$key])) {
            return;
        }

        $this->fails = true;
        
        $this->last_message = $this->lexique('regex', $key);
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }
}
