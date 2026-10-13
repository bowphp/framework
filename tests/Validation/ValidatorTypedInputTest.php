<?php

namespace Bow\Tests\Validation;

use Bow\Tests\Config\TestingConfiguration;
use Bow\Translate\Translator;
use Bow\Validation\Validator;

/**
 * Regression tests for inputs that arrive with native PHP types, as they do
 * from a JSON request body (booleans, integers, floats, arrays), rather than
 * the strings a form submission produces. These used to throw TypeError
 * (HTTP 500) because the rules passed raw values to string-typed Str helpers.
 *
 * Deliberately needs no database so it can run anywhere.
 */
class ValidatorTypedInputTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Translator::configure($config['translate.lang'], $config["translate.dictionary"]);
    }

    // ==================== nullable ====================

    public function test_nullable_boolean_accepts_real_true()
    {
        $validation = Validator::make(['flag' => true], ['flag' => 'nullable|boolean']);
        $this->assertFalse($validation->fails());
    }

    public function test_nullable_boolean_accepts_real_false()
    {
        // false is a value, not an absence: it must reach the boolean rule and pass.
        $validation = Validator::make(['flag' => false], ['flag' => 'nullable|boolean']);
        $this->assertFalse($validation->fails());
    }

    public function test_nullable_boolean_still_rejects_non_boolean_string()
    {
        $validation = Validator::make(['flag' => 'yes'], ['flag' => 'nullable|boolean']);
        $this->assertTrue($validation->fails());
        $this->assertSame(['flag'], $validation->getCorruptedFields());
    }

    public function test_nullable_boolean_rejects_array_without_throwing()
    {
        $validation = Validator::make(['flag' => ['a']], ['flag' => 'nullable|boolean']);
        $this->assertTrue($validation->fails());
    }

    public function test_nullable_accepts_native_integer_and_float()
    {
        $this->assertFalse(Validator::make(['n' => 5], ['n' => 'nullable|int'])->fails());
        $this->assertFalse(Validator::make(['n' => 0], ['n' => 'nullable|int'])->fails());
        $this->assertFalse(Validator::make(['n' => 3.5], ['n' => 'nullable|number'])->fails());
    }

    public function test_nullable_treats_null_empty_string_and_empty_array_as_absent()
    {
        $this->assertFalse(Validator::make(['n' => null], ['n' => 'nullable|int'])->fails());
        $this->assertFalse(Validator::make(['n' => ''], ['n' => 'nullable|int'])->fails());
        $this->assertFalse(Validator::make(['n' => '   '], ['n' => 'nullable|int'])->fails());
        $this->assertFalse(Validator::make(['n' => []], ['n' => 'nullable|int'])->fails());
        $this->assertFalse(Validator::make([], ['n' => 'nullable|int'])->fails());
    }

    public function test_nullable_zero_values_are_not_treated_as_absent()
    {
        // 0 and false are real values and must be validated by the following rules.
        $this->assertTrue(Validator::make(['n' => 0], ['n' => 'nullable|email'])->fails());
        $this->assertTrue(Validator::make(['n' => false], ['n' => 'nullable|email'])->fails());
    }

    // ==================== max / min / size ====================

    public function test_max_compares_native_numbers_numerically()
    {
        $this->assertFalse(Validator::make(['n' => 5], ['n' => 'required|max:10'])->fails());
        $this->assertFalse(Validator::make(['n' => 10], ['n' => 'required|max:10'])->fails());
        $this->assertTrue(Validator::make(['n' => 11], ['n' => 'required|max:10'])->fails());
        $this->assertTrue(Validator::make(['n' => 10.5], ['n' => 'required|max:10'])->fails());
    }

    public function test_min_compares_native_numbers_numerically()
    {
        $this->assertFalse(Validator::make(['n' => 3], ['n' => 'required|min:3'])->fails());
        $this->assertFalse(Validator::make(['n' => 3.5], ['n' => 'required|min:3'])->fails());
        $this->assertTrue(Validator::make(['n' => 2], ['n' => 'required|min:3'])->fails());
    }

    public function test_size_compares_native_numbers_numerically()
    {
        $this->assertFalse(Validator::make(['n' => 3], ['n' => 'required|size:3'])->fails());
        $this->assertTrue(Validator::make(['n' => 30], ['n' => 'required|size:3'])->fails());
    }

    public function test_max_min_size_keep_length_semantics_for_strings()
    {
        // Numeric strings (form input) stay on length semantics, so a phone
        // number declared max:10 keeps working.
        $this->assertFalse(Validator::make(['s' => '0612345678'], ['s' => 'required|max:10'])->fails());
        $this->assertTrue(Validator::make(['s' => '1234'], ['s' => 'required|max:3'])->fails());
        $this->assertTrue(Validator::make(['s' => 'fr'], ['s' => 'required|min:5'])->fails());
        $this->assertFalse(Validator::make(['s' => 'bow'], ['s' => 'required|size:3'])->fails());
    }

    public function test_max_min_size_fail_on_boolean_and_array_without_throwing()
    {
        $this->assertTrue(Validator::make(['v' => true], ['v' => 'required|max:3'])->fails());
        $this->assertTrue(Validator::make(['v' => false], ['v' => 'required|min:1'])->fails());
        $this->assertTrue(Validator::make(['v' => ['a']], ['v' => 'required|size:1'])->fails());
    }

    // ==================== string-only rules ====================

    public function test_string_only_rules_fail_on_non_strings_without_throwing()
    {
        $this->assertTrue(Validator::make(['v' => 5], ['v' => 'required|alpha'])->fails());
        $this->assertTrue(Validator::make(['v' => true], ['v' => 'required|alphanum'])->fails());
        $this->assertTrue(Validator::make(['v' => 1.5], ['v' => 'required|lower'])->fails());
        $this->assertTrue(Validator::make(['v' => false], ['v' => 'required|upper'])->fails());
        $this->assertTrue(Validator::make(['v' => true], ['v' => 'required|email'])->fails());
        $this->assertTrue(Validator::make(['v' => ['a@b.c']], ['v' => 'required|email'])->fails());
    }

    public function test_string_only_rules_still_pass_on_valid_strings()
    {
        $this->assertFalse(Validator::make(['v' => 'bow'], ['v' => 'required|alpha'])->fails());
        $this->assertFalse(Validator::make(['v' => 'bow5'], ['v' => 'required|alphanum'])->fails());
        $this->assertFalse(Validator::make(['v' => 'bow'], ['v' => 'required|lower'])->fails());
        $this->assertFalse(Validator::make(['v' => 'BOW'], ['v' => 'required|upper'])->fails());
        $this->assertFalse(Validator::make(['v' => 'bow@bowphp.com'], ['v' => 'required|email'])->fails());
    }

    // ==================== full JSON payload ====================

    public function test_typical_json_payload_validates_end_to_end()
    {
        $payload = [
            'name' => 'Milou',
            'active' => false,
            'age' => 7,
            'score' => 9.5,
            'tags' => ['dog'],
            'nickname' => null,
        ];

        $validation = Validator::make($payload, [
            'name' => 'required|alpha|max:50',
            'active' => 'nullable|boolean',
            'age' => 'required|int|min:0|max:120',
            'score' => 'nullable|number|between:0,10',
            'nickname' => 'nullable|alpha',
        ]);

        $this->assertFalse($validation->fails(), json_encode($validation->getMessages()));
    }
}
