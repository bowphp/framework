<?php

namespace Bow\Validation;

use Bow\Database\Database;
use Bow\Support\Str;

class Validator
{
    use FieldLexical;

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
     * The user messages
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Define the valid rule list
     *
     * @var array
     */
    protected $rules = [
        'Required',
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
        'Regex',
        'Float',
        'Date',
        'DateTime',
        'NotExists',
        'Unique',
        'Exists',
    ];

    /**
     * The lexical
     *
     * @var array
     */
    private $lexical;

    /**
     * Validator constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->lexical = require __DIR__.'/stubs/lexical.php';
    }

    /**
     * Any possible markers.
     *
     * @param array $inputs
     * @param array $rules
     * @param array $messages
     *
     * @return Validate
     */
    public static function make(array $inputs, array $rules, array $messages = [])
    {
        $v = new static();

        $v->setCustomMessages($messages);

        return $v->validate($inputs, $rules);
    }

    /**
     * Set the user custom message
     *
     * @param array $messages
     */
    public function setCustomMessages(array $messages)
    {
        $this->messages = $messages;
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
                // In the box there is a | super flux.
                if (is_int($masque) || Str::len($masque) == "") {
                    continue;
                }

                // Error lists.
                $this->errors[$key] = [];

                // Mask on the required rule
                foreach ($this->rules as $rule) {
                    $this->{'compile'.$rule}($key, $masque);
                    if ($rule == 'Required' && $this->fails) {
                        break;
                    }
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
     * Compile Required Rule
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileRequired($key, $masque)
    {
        if (!isset($this->inputs[$key])
            || is_null($this->inputs[$key])
            || $this->inputs[$key] === ''
        ) {
            $this->last_message = $message = $this->lexical('required', $key);
            
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
     * @return void
     */
    protected function compileEmpty($key, $masque)
    {
        if (!isset($this->inputs[$key])) {
            $this->fails = true;

            $this->last_message = $message = $this->lexical('empty', $key);

            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $message
            ];
        }
    }

    /**
     * Compile Min Mask
     *
     * [min:value] Check that the content of the field is a number of
     * minimal character following the defined value
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileMin($key, $masque)
    {
        if (!preg_match("/^min:(\d+)$/", $masque, $match)) {
            return;
        }

        $length = (int) end($match);

        if (Str::len($this->inputs[$key]) >= $length) {
            return;
        }

        $this->fails = true;
        
        $this->last_message = $this->lexical('min', [
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
     * @return void
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
        
        $this->last_message = $this->lexical('max', [
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
     * @return void
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

        $this->last_message = $this->lexical('same', [
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
     * @return void
     */
    protected function compileEmail($key, $masque)
    {
        if (!preg_match("/^email$/", $masque, $match)) {
            return;
        }

        if (Str::isMail($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('email', $key);

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
     * @return void
     */
    protected function compileNumber($key, $masque)
    {
        if (!preg_match("/^number$/", $masque, $match)) {
            return;
        }

        if (is_numeric($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('number', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Int Rule
     *
     * [int] Check that the contents of the field is an integer number
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileInt($key, $masque)
    {
        if (!preg_match("/^int$/", $masque, $match)) {
            return;
        }

        if (is_int($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('int', $key);
        
        $this->fails = true;
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Float Rule
     *
     * [float] Check that the field content is a float number
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileFloat($key, $masque)
    {
        if (!preg_match("/^float$/", $masque, $match)) {
            return;
        }

        if (is_float($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('float', $key);

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
     * @return void
     */
    protected function compileAlphaNum($key, $masque)
    {
        if (!preg_match("/^alphanum$/", $masque)) {
            return;
        }

        if (Str::isAlphaNum($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('alphanum', $key);
        
        $this->fails = true;
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile In Rule
     *
     * [in:(value, ...)] Check that the contents of the field are equal to the defined value
     *
     * @param string $key
     * @param string $masque
     * @return void
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

        $this->last_message = $this->lexical('in', [
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
     * @return void
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

        $this->last_message = $this->lexical('size', [
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
     * @return void
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
        
        $this->last_message = $this->lexical('lower', $key);

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
     * @return void
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
        
        $this->last_message = $this->lexical('upper', $key);
        
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
     * @return void
     */
    protected function compileAlpha($key, $masque)
    {
        if (!preg_match("/^alpha$/", $masque)) {
            return;
        }

        if (Str::isAlpha($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('alpha', $key);
        
        $this->fails = true;
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Exists Rule
     *
     * [exists:table,column] Check that the contents of a table field exist
     *
     * @param string $key
     * @param string $masque
     * @return void
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
            $this->last_message = $this->lexical('exists', $key);
            
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
     * [!exists:table,column] Checks that the contents of the field of a table do not exist
     *
     * @param string $key
     * @param string $masque
     * @return void
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
            $this->last_message = $this->lexical('not_exists', $key);
            
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
     * [unique:table,column] Check that the contents of the field of a table is a single value
     *
     * @param string $key
     * @param string $masque
     * @return void
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
            $this->last_message = $this->lexical('unique', $key);
            
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
     * @return void
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
        
        $this->last_message = $this->lexical('date', $key);
        
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
     * @return void
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

        $this->last_message = $this->lexical('datetime', $key);
        
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
     * @return void
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
        
        $this->last_message = $this->lexical('regex', $key);
        
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }
}
