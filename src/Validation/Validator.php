<?php

declare(strict_types=1);

namespace Bow\Validation;

use Bow\Support\Str;
use Bow\Validation\Rules\DatabaseRule;
use Bow\Validation\Rules\DatetimeRule;
use Bow\Validation\Rules\EmailRule;
use Bow\Validation\Rules\NumericRule;
use Bow\Validation\Rules\RegexRule;
use Bow\Validation\Rules\StringRule;

class Validator
{
    use FieldLexical;
    use DatabaseRule;
    use DatetimeRule;
    use EmailRule;
    use NumericRule;
    use StringRule;
    use RegexRule;

    /**
     * The Fails flag
     *
     * @var bool
     */
    protected bool $fails = false;

    /**
     * The last name
     *
     * @var ?string
     */
    protected ?string $last_message = null;

    /**
     * The errors list
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * The validation DATA
     *
     * @var array
     */
    protected array $inputs = [];

    /**
     * The user messages
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * Define the valid rule list
     *
     * @var array
     */
    protected array $rules = [
        'Required',
        "RequiredIf",
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
    private array $lexical;

    /**
     * Validator constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->lexical = require __DIR__ . '/stubs/lexical.php';
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
    public static function make(array $inputs, array $rules, array $messages = []): Validate
    {
        $validator = new Validator();

        $validator->setCustomMessages($messages);

        return $validator->validate($inputs, $rules);
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
    public function validate(array $inputs, array $rules): Validate
    {
        $this->inputs = $inputs;

        /**
         * Formatting and validation of each rule
         * eg. name => "required|max:100|alpha"
         */
        foreach ($rules as $key => $rule) {
            foreach (explode("|", $rule) as $masque) {
                // In the box there is a | super flux.
                if (is_int($masque) || Str::len($masque) == "") {
                    continue;
                }

                // Mask on the required rule
                foreach ($this->rules as $rule) {
                    $this->{'compile' . $rule}($key, $masque);
                    if ($rule == 'Required' && $this->fails) {
                        break;
                    }
                }
            }
        }

        return new Validate(
            $this->fails,
            $this->last_message,
            $this->errors
        );
    }
}
