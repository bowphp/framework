<?php

namespace Bow\Tests\Validation;

use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Translate\Translator;
use Bow\Validation\Validator;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
        Translator::configure($config['translate.lang'], $config["translate.dictionary"]);

        Database::statement("drop table if exists pets;");
        Database::statement("create table pets (id int primary key, name varchar(225));");
        Database::insert("insert into pets values(1, 'Milou'), (2, 'Milou');");
    }

    // ==================== String Rules ====================

    public function test_required_rule_passes_with_value()
    {
        $validation = Validator::make(['name' => 'Milou'], ['name' => 'required']);
        $this->assertFalse($validation->fails());
    }

    public function test_required_rule_fails_without_field()
    {
        $validation = Validator::make(['name' => 'Couli'], ['lastname' => 'required']);
        $this->assertTrue($validation->fails());
    }

    public function test_required_rule_fails_with_empty_string()
    {
        $validation = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertTrue($validation->fails());
    }

    public function test_required_rule_fails_with_null()
    {
        $validation = Validator::make(['name' => null], ['name' => 'required']);
        $this->assertTrue($validation->fails());
    }

    public function test_required_if_rule_passes_when_condition_field_not_present()
    {
        $validation = Validator::make(['name' => 'Couli'], ['lastname' => 'required_if:username']);
        $this->assertFalse($validation->fails());
    }

    public function test_required_if_rule_fails_when_condition_field_present()
    {
        $validation = Validator::make(['name' => 'Milou'], ['lastname' => 'required_if:name']);
        $this->assertTrue($validation->fails());
    }

    public function test_required_if_rule_passes_when_condition_field_present_with_value()
    {
        $validation = Validator::make(['name' => 'Milou', 'lastname' => 'Dog'], ['lastname' => 'required_if:name']);
        $this->assertFalse($validation->fails());
    }

    public function test_in_rule_passes_with_valid_value()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|in:bow,framework']);
        $this->assertFalse($validation->fails());
    }

    public function test_in_rule_fails_with_invalid_value()
    {
        $validation = Validator::make(['name' => 'papac'], ['name' => 'required|in:bow,framework']);
        $this->assertTrue($validation->fails());
    }

    public function test_in_rule_passes_with_multiple_valid_values()
    {
        $validation = Validator::make(['name' => 'framework'], ['name' => 'required|in:bow,framework,php']);
        $this->assertFalse($validation->fails());
    }

    public function test_same_rule_passes_with_matching_value()
    {
        $validation = Validator::make(['name' => 1], ['name' => 'required|same:1']);
        $this->assertFalse($validation->fails());
    }

    public function test_same_rule_fails_with_different_value()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|same:framework']);
        $this->assertTrue($validation->fails());
    }

    public function test_same_rule_passes_with_string_match()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|same:bow']);
        $this->assertFalse($validation->fails());
    }

    public function test_max_rule_passes_within_limit()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|max:3']);
        $this->assertFalse($validation->fails());
    }

    public function test_max_rule_fails_exceeding_limit()
    {
        $validation = Validator::make(['name' => 'framework'], ['name' => 'required|max:5']);
        $this->assertTrue($validation->fails());
    }

    public function test_max_rule_passes_at_exact_limit()
    {
        $validation = Validator::make(['name' => 'bowframework'], ['name' => 'required|max:12']);
        $this->assertFalse($validation->fails());
    }

    public function test_min_rule_passes_above_minimum()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|min:3']);
        $this->assertFalse($validation->fails());
    }

    public function test_min_rule_fails_below_minimum()
    {
        $validation = Validator::make(['name' => 'fr'], ['name' => 'required|min:5']);
        $this->assertTrue($validation->fails());
    }

    public function test_min_rule_passes_at_exact_minimum()
    {
        $validation = Validator::make(['name' => 'bowfw'], ['name' => 'required|min:5']);
        $this->assertFalse($validation->fails());
    }

    public function test_lower_rule_passes_with_lowercase()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|lower']);
        $this->assertFalse($validation->fails());
    }

    public function test_lower_rule_fails_with_uppercase()
    {
        $validation = Validator::make(['name' => 'BOW'], ['name' => 'required|lower']);
        $this->assertTrue($validation->fails());
    }

    public function test_lower_rule_fails_with_mixed_case()
    {
        $validation = Validator::make(['name' => 'Bow'], ['name' => 'required|lower']);
        $this->assertTrue($validation->fails());
    }

    public function test_upper_rule_passes_with_uppercase()
    {
        $validation = Validator::make(['name' => 'BOW'], ['name' => 'required|upper']);
        $this->assertFalse($validation->fails());
    }

    public function test_upper_rule_fails_with_lowercase()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|upper']);
        $this->assertTrue($validation->fails());
    }

    public function test_upper_rule_fails_with_mixed_case()
    {
        $validation = Validator::make(['name' => 'Bow'], ['name' => 'required|upper']);
        $this->assertTrue($validation->fails());
    }

    public function test_size_rule_passes_with_exact_length()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|size:3']);
        $this->assertFalse($validation->fails());
    }

    public function test_size_rule_fails_with_different_length()
    {
        $validation = Validator::make(['name' => 'framework'], ['name' => 'required|size:5']);
        $this->assertTrue($validation->fails());
    }

    public function test_size_rule_fails_with_shorter_length()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|size:5']);
        $this->assertTrue($validation->fails());
    }

    public function test_alpha_rule_passes_with_letters_only()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|alpha']);
        $this->assertFalse($validation->fails());
    }

    public function test_alpha_rule_fails_with_numbers()
    {
        $validation = Validator::make(['name' => 'bow223'], ['name' => 'required|alpha']);
        $this->assertTrue($validation->fails());
    }

    public function test_alpha_rule_fails_with_special_characters()
    {
        $validation = Validator::make(['name' => 'bow!@#'], ['name' => 'required|alpha']);
        $this->assertTrue($validation->fails());
    }

    public function test_alpha_num_passes_with_letters_and_numbers()
    {
        $validation = Validator::make(['name' => 'bow223'], ['name' => 'required|alphanum']);
        $this->assertFalse($validation->fails());
    }

    public function test_alpha_num_fails_with_special_characters()
    {
        $validation = Validator::make(['name' => 'bow!223'], ['name' => 'required|alphanum']);
        $this->assertTrue($validation->fails());
    }

    public function test_alpha_num_passes_with_only_letters()
    {
        $validation = Validator::make(['name' => 'bowframework'], ['name' => 'required|alphanum']);
        $this->assertFalse($validation->fails());
    }

    public function test_alpha_num_passes_with_only_numbers()
    {
        $validation = Validator::make(['name' => '12345'], ['name' => 'required|alphanum']);
        $this->assertFalse($validation->fails());
    }

    // ==================== Numeric Rules ====================

    public function test_number_rule_passes_with_integer()
    {
        $validation = Validator::make(['price' => 1], ['price' => 'required|number']);
        $this->assertFalse($validation->fails());
    }

    public function test_number_rule_fails_with_string()
    {
        $validation = Validator::make(['price' => 'bow'], ['price' => 'required|number']);
        $this->assertTrue($validation->fails());
    }

    public function test_number_rule_passes_with_float()
    {
        $validation = Validator::make(['price' => 10.5], ['price' => 'required|number']);
        $this->assertFalse($validation->fails());
    }

    public function test_number_rule_passes_with_negative_number()
    {
        $validation = Validator::make(['price' => -10], ['price' => 'required|number']);
        $this->assertFalse($validation->fails());
    }

    public function test_number_rule_passes_with_numeric_string()
    {
        $validation = Validator::make(['price' => '123'], ['price' => 'required|number']);
        $this->assertFalse($validation->fails());
    }

    public function test_int_rule_passes_with_integer()
    {
        $validation = Validator::make(['name' => 1], ['name' => 'required|int']);
        $this->assertFalse($validation->fails());
    }

    public function test_int_rule_fails_with_string()
    {
        $validation = Validator::make(['name' => 'bow'], ['name' => 'required|int']);
        $this->assertTrue($validation->fails());
    }

    public function test_int_rule_fails_with_float()
    {
        $validation = Validator::make(['name' => 1.5], ['name' => 'required|int']);
        $this->assertTrue($validation->fails());
    }

    public function test_int_rule_passes_with_negative_integer()
    {
        $validation = Validator::make(['name' => -10], ['name' => 'required|int']);
        $this->assertFalse($validation->fails());
    }

    public function test_float_rule_passes_with_float()
    {
        $validation = Validator::make(['price' => 10.5], ['price' => 'required|float']);
        $this->assertFalse($validation->fails());
    }

    public function test_float_rule_fails_with_integer()
    {
        $validation = Validator::make(['price' => 10], ['price' => 'required|float']);
        $this->assertTrue($validation->fails());
    }

    public function test_float_rule_fails_with_string()
    {
        $validation = Validator::make(['price' => 'bow'], ['price' => 'required|float']);
        $this->assertTrue($validation->fails());
    }

    public function test_float_rule_passes_with_negative_float()
    {
        $validation = Validator::make(['price' => -10.5], ['price' => 'required|float']);
        $this->assertFalse($validation->fails());
    }

    // ==================== Email Rule ====================

    public function test_email_rule_passes_with_valid_email()
    {
        $validation = Validator::make(['email' => 'dakiafranck@gmail.com'], ['email' => 'required|email']);
        $this->assertFalse($validation->fails());
    }

    public function test_email_rule_fails_with_invalid_email()
    {
        $validation = Validator::make(['email' => 'bow'], ['email' => 'required|email']);
        $this->assertTrue($validation->fails());
    }

    public function test_email_rule_fails_without_at_symbol()
    {
        $validation = Validator::make(['email' => 'bowframework.com'], ['email' => 'required|email']);
        $this->assertTrue($validation->fails());
    }

    public function test_email_rule_fails_without_domain()
    {
        $validation = Validator::make(['email' => 'test@'], ['email' => 'required|email']);
        $this->assertTrue($validation->fails());
    }

    public function test_email_rule_passes_with_subdomain()
    {
        $validation = Validator::make(['email' => 'test@mail.example.com'], ['email' => 'required|email']);
        $this->assertFalse($validation->fails());
    }

    // ==================== Database Rules ====================

    public function test_exists_rule_passes_with_existing_value()
    {
        $validation = Validator::make(['name' => 'Milou'], ['name' => 'required|exists:pets,name']);
        $this->assertFalse($validation->fails());
    }

    public function test_exists_rule_fails_with_non_existing_value()
    {
        $validation = Validator::make(['name' => 'Couli'], ['name' => 'required|exists:pets']);
        $this->assertTrue($validation->fails());
    }

    public function test_exists_rule_passes_without_column_specification()
    {
        $validation = Validator::make(['name' => 'Milou'], ['name' => 'required|exists:pets']);
        $this->assertFalse($validation->fails());
    }

    public function test_not_exists_rule_passes_with_non_existing_value()
    {
        $validation = Validator::make(['name' => 'Couli'], ['name' => 'required|!exists:pets,name']);
        $this->assertFalse($validation->fails());
    }

    public function test_not_exists_rule_fails_with_existing_value()
    {
        $validation = Validator::make(['name' => 'Milou'], ['name' => 'required|!exists:pets']);
        $this->assertTrue($validation->fails());
    }

    public function test_unique_rule_passes_with_unique_value()
    {
        Database::insert("insert into pets values(3, 'Couli');");

        $validation = Validator::make(['name' => 'Couli'], ['name' => 'required|unique:pets,name']);
        $this->assertFalse($validation->fails());
    }

    public function test_unique_rule_fails_with_duplicate_value()
    {
        $validation = Validator::make(['name' => 'Milou'], ['name' => 'required|unique:pets']);
        $this->assertTrue($validation->fails());
    }

    public function test_unique_rule_fails_when_value_becomes_duplicate()
    {
        Database::insert("insert into pets values(4, 'Couli');");

        $validation = Validator::make(['name' => 'Couli'], ['name' => 'required|unique:pets,name']);
        $this->assertTrue($validation->fails());
    }

    // ==================== Date/Time Rules ====================

    public function test_date_rule_passes_with_valid_date()
    {
        $validation = Validator::make(['created_at' => '2024-01-15'], ['created_at' => 'required|date']);
        $this->assertFalse($validation->fails());
    }

    public function test_date_rule_fails_with_invalid_date()
    {
        $validation = Validator::make(['created_at' => '15-01-2024'], ['created_at' => 'required|date']);
        $this->assertTrue($validation->fails());
    }

    public function test_date_rule_fails_with_invalid_format()
    {
        $validation = Validator::make(['created_at' => 'not-a-date'], ['created_at' => 'required|date']);
        $this->assertTrue($validation->fails());
    }

    public function test_date_time_rule_passes_with_valid_datetime()
    {
        $validation = Validator::make(
            ['created_at' => '2024-01-15 10:30:00'],
            ['created_at' => 'required|datetime']
        );
        $this->assertFalse($validation->fails());
    }

    public function test_date_time_rule_fails_with_invalid_datetime()
    {
        $validation = Validator::make(
            ['created_at' => '01-10-2024 10:30:00'],
            ['created_at' => 'required|datetime']
        );
        $this->assertTrue($validation->fails());
    }

    public function test_date_time_rule_fails_with_date_only()
    {
        $validation = Validator::make(
            ['created_at' => '2024-01-15'],
            ['created_at' => 'required|datetime']
        );
        $this->assertTrue($validation->fails());
    }

    public function test_regex_rule_passes_with_matching_pattern()
    {
        $validation = Validator::make(['code' => 'ABC123'], ['code' => 'required|regex:^[A-Z]{3}\d{3}$']);
        $this->assertFalse($validation->fails());
    }

    public function test_regex_rule_fails_with_non_matching_pattern()
    {
        $validation = Validator::make(['code' => 'abc123'], ['code' => 'required|regex:^[A-Z]{3}\d{3}$']);
        $this->assertTrue($validation->fails());
    }

    public function test_regex_rule_passes_with_phone_number_pattern()
    {
        $validation = Validator::make(
            ['phone' => '+225-0708090602'],
            ['phone' => 'required|regex:^\+\d{3}-\d{10}$']
        );
        $this->assertFalse($validation->fails());
    }

    public function test_regex_rule_fails_with_invalid_phone_format()
    {
        $validation = Validator::make(
            ['phone' => '0708090602'],
            ['phone' => 'required|regex:^\+\d{3}-\d{10}$']
        );
        $this->assertTrue($validation->fails());
    }

    // ==================== Nullable Rule ====================

    public function test_nullable_rule_passes_with_null_value()
    {
        $validation = Validator::make(['name' => null], ['name' => 'nullable']);
        $this->assertFalse($validation->fails());
    }

    public function test_nullable_rule_passes_with_missing_field()
    {
        $validation = Validator::make([], ['name' => 'nullable']);
        $this->assertFalse($validation->fails());
    }

    public function test_nullable_rule_passes_with_value()
    {
        $validation = Validator::make(['name' => 'Bow'], ['name' => 'nullable']);

        $this->assertFalse($validation->fails());
    }

    public function test_nullable_and_required_rule_fails_with_null()
    {
        $validation = Validator::make(['name' => null], ['name' => 'nullable|required']);
        $this->assertTrue($validation->fails());
    }

    public function test_nullable_and_required_rule_passes_with_value()
    {
        $validation = Validator::make(['name' => 'Bow'], ['name' => 'nullable|required']);

        $this->assertFalse($validation->fails());
    }

    // ==================== Url Rule ====================

    public function test_url_rule_passes_with_valid_url()
    {
        $validation = Validator::make(['site' => 'https://example.com/path?x=1'], ['site' => 'url']);
        $this->assertFalse($validation->fails());
    }

    public function test_url_rule_fails_with_invalid_url()
    {
        $validation = Validator::make(['site' => 'not-a-url'], ['site' => 'url']);
        $this->assertTrue($validation->fails());
    }

    // ==================== Ip Rule ====================

    public function test_ip_rule_passes_with_ipv4()
    {
        $validation = Validator::make(['addr' => '192.168.1.1'], ['addr' => 'ip']);
        $this->assertFalse($validation->fails());
    }

    public function test_ip_rule_passes_with_ipv6()
    {
        $validation = Validator::make(['addr' => '::1'], ['addr' => 'ip']);
        $this->assertFalse($validation->fails());
    }

    public function test_ip_rule_v4_rejects_ipv6()
    {
        $validation = Validator::make(['addr' => '::1'], ['addr' => 'ip:v4']);
        $this->assertTrue($validation->fails());
    }

    public function test_ip_rule_v6_rejects_ipv4()
    {
        $validation = Validator::make(['addr' => '192.168.1.1'], ['addr' => 'ip:v6']);
        $this->assertTrue($validation->fails());
    }

    public function test_ip_rule_fails_with_garbage()
    {
        $validation = Validator::make(['addr' => '999.999.999.999'], ['addr' => 'ip']);
        $this->assertTrue($validation->fails());
    }

    // ==================== Boolean Rule ====================

    public function test_boolean_rule_passes_with_boolean_values()
    {
        foreach ([true, false, 0, 1, '0', '1', 'true', 'false'] as $value) {
            $validation = Validator::make(['flag' => $value], ['flag' => 'boolean']);
            $this->assertFalse($validation->fails(), 'Failed for ' . var_export($value, true));
        }
    }

    public function test_boolean_rule_accepts_bool_alias()
    {
        $validation = Validator::make(['flag' => true], ['flag' => 'bool']);
        $this->assertFalse($validation->fails());
    }

    public function test_boolean_rule_fails_with_non_boolean()
    {
        $validation = Validator::make(['flag' => 'yes'], ['flag' => 'boolean']);
        $this->assertTrue($validation->fails());
    }

    // ==================== Json Rule ====================

    public function test_json_rule_passes_with_valid_json()
    {
        $validation = Validator::make(['payload' => '{"a":1,"b":[2,3]}'], ['payload' => 'json']);
        $this->assertFalse($validation->fails());
    }

    public function test_json_rule_fails_with_invalid_json()
    {
        $validation = Validator::make(['payload' => '{not json}'], ['payload' => 'json']);
        $this->assertTrue($validation->fails());
    }

    public function test_json_rule_fails_with_empty_string()
    {
        $validation = Validator::make(['payload' => ''], ['payload' => 'json']);
        $this->assertTrue($validation->fails());
    }

    // ==================== Uuid Rule ====================

    public function test_uuid_rule_passes_with_valid_uuid_v4()
    {
        $validation = Validator::make(
            ['id' => '550e8400-e29b-41d4-a716-446655440000'],
            ['id' => 'uuid']
        );
        $this->assertFalse($validation->fails());
    }

    public function test_uuid_rule_fails_with_invalid_uuid()
    {
        $validation = Validator::make(['id' => 'not-a-uuid'], ['id' => 'uuid']);
        $this->assertTrue($validation->fails());
    }

    public function test_uuid_rule_fails_with_wrong_format()
    {
        $validation = Validator::make(
            ['id' => '550e8400e29b41d4a716446655440000'], // no dashes
            ['id' => 'uuid']
        );
        $this->assertTrue($validation->fails());
    }

    // ==================== Confirmed Rule ====================

    public function test_confirmed_rule_passes_when_matching()
    {
        $validation = Validator::make(
            ['password' => 'secret', 'password_confirmation' => 'secret'],
            ['password' => 'confirmed']
        );
        $this->assertFalse($validation->fails());
    }

    public function test_confirmed_rule_fails_when_mismatched()
    {
        $validation = Validator::make(
            ['password' => 'secret', 'password_confirmation' => 'other'],
            ['password' => 'confirmed']
        );
        $this->assertTrue($validation->fails());
    }

    public function test_confirmed_rule_fails_when_confirmation_missing()
    {
        $validation = Validator::make(['password' => 'secret'], ['password' => 'confirmed']);
        $this->assertTrue($validation->fails());
    }

    // ==================== Different Rule ====================

    public function test_different_rule_passes_when_values_differ()
    {
        $validation = Validator::make(
            ['username' => 'alice', 'email' => 'alice@example.com'],
            ['username' => 'different:email']
        );
        $this->assertFalse($validation->fails());
    }

    public function test_different_rule_fails_when_values_match()
    {
        $validation = Validator::make(
            ['old_password' => 'secret', 'new_password' => 'secret'],
            ['new_password' => 'different:old_password']
        );
        $this->assertTrue($validation->fails());
    }

    // ==================== Between Rule ====================

    public function test_between_rule_passes_for_string_length_in_range()
    {
        $validation = Validator::make(['name' => 'Milou'], ['name' => 'between:3,10']);
        $this->assertFalse($validation->fails());
    }

    public function test_between_rule_fails_for_string_length_too_short()
    {
        $validation = Validator::make(['name' => 'Mi'], ['name' => 'between:3,10']);
        $this->assertTrue($validation->fails());
    }

    public function test_between_rule_fails_for_string_length_too_long()
    {
        $validation = Validator::make(
            ['name' => 'A very, very long name indeed'],
            ['name' => 'between:3,10']
        );
        $this->assertTrue($validation->fails());
    }

    public function test_between_rule_passes_for_numeric_value_in_range()
    {
        $validation = Validator::make(['age' => 25], ['age' => 'between:18,65']);
        $this->assertFalse($validation->fails());
    }

    public function test_between_rule_fails_for_numeric_value_out_of_range()
    {
        $validation = Validator::make(['age' => 5], ['age' => 'between:18,65']);
        $this->assertTrue($validation->fails());
    }
}
