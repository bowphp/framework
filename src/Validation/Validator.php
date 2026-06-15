<?php

declare(strict_types=1);

namespace Bow\Validation;

use Bow\Support\Str;
use Bow\Validation\Rules\BetweenRule;
use Bow\Validation\Rules\BooleanRule;
use Bow\Validation\Rules\ConfirmedRule;
use Bow\Validation\Rules\DatabaseRule;
use Bow\Validation\Rules\DatetimeRule;
use Bow\Validation\Rules\DifferentRule;
use Bow\Validation\Rules\EmailRule;
use Bow\Validation\Rules\IpRule;
use Bow\Validation\Rules\JsonRule;
use Bow\Validation\Rules\NullableRule;
use Bow\Validation\Rules\NumericRule;
use Bow\Validation\Rules\RegexRule;
use Bow\Validation\Rules\StringRule;
use Bow\Validation\Rules\UrlRule;
use Bow\Validation\Rules\UuidRule;

class Validator
{
    use FieldLexical;
    use DatabaseRule;
    use DatetimeRule;
    use EmailRule;
    use NumericRule;
    use StringRule;
    use RegexRule;
    use NullableRule;
    use UrlRule;
    use IpRule;
    use BooleanRule;
    use JsonRule;
    use UuidRule;
    use ConfirmedRule;
    use DifferentRule;
    use BetweenRule;

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
        'Nullable',
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
        'Url',
        'Ip',
        'Boolean',
        'Json',
        'Uuid',
        'Confirmed',
        'Different',
        'Between',
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
        $this->lexical = include __DIR__ . '/stubs/lexical.php';
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
        foreach ($rules as $field => $rule) {
            $this->checkRule($rule, $field);
        }

        return new Validate(
            $this->fails,
            $this->last_message,
            $this->errors
        );
    }

    /**
     * Check atomic rule
     *
     * @param string $rule
     * @param string $field
     * @return void
     */
    private function checkRule(string $rule, string $field): void
    {
        $masques = explode("|", $rule);
        // `required` always runs, even when `nullable` matched — an explicit
        // `required` is an unconditional contract.
        $required_declared = in_array('required', $masques, true);

        foreach ($masques as $masque) {
            // In the box there is a | super flux.
            if (is_int($masque) || Str::len($masque) == "") {
                continue;
            }

            if ($masque == "nullable" && $this->compileNullable($field, $masque)) {
                if ($required_declared) {
                    continue;
                }
                break;
            }

            // Mask on the required rule
            foreach ($this->rules as $rule_item) {
                $this->{'compile' . $rule_item}($field, $masque);
                if ($rule_item == 'Required' && $this->fails) {
                    break;
                }
            }
        }
    }
}
