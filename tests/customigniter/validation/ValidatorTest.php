<?php
declare(strict_types=1);

use Customigniter\Validation\Validator;

class ValidatorTest extends CI_TestCase
{
	private Validator $validator;

	// --------------------------------------------------------------------

	public function set_up(): void
	{
		$this->validator = new Validator();
	}

	// --------------------------------------------------------------------

	public function test_run_without_rules_passes(): void
	{
		$this->assertTrue($this->validator->run(['anything' => 'goes']));
		$this->assertSame([], $this->validator->errors());
	}

	// --------------------------------------------------------------------

	public function test_required_accepts_present_values(): void
	{
		$this->validator->setRules(['name' => 'required']);

		$this->assertTrue($this->validator->run(['name' => 'Ada']));
		$this->assertTrue($this->validator->run(['name' => '0']));
		$this->assertTrue($this->validator->run(['name' => 0]));
	}

	// --------------------------------------------------------------------

	public function test_required_rejects_missing_empty_and_null(): void
	{
		$this->validator->setRules(['name' => 'required']);

		$this->assertFalse($this->validator->run([]));
		$this->assertFalse($this->validator->run(['name' => '']));
		$this->assertFalse($this->validator->run(['name' => null]));
		$this->assertFalse($this->validator->run(['name' => []]));
		$this->assertSame(['The Name field is required.'], $this->validator->errorsFor('name'));
	}

	// --------------------------------------------------------------------

	public function test_optional_field_without_value_is_skipped(): void
	{
		$this->validator->setRules(['nickname' => 'max_length[10]']);

		$this->assertTrue($this->validator->run([]));
		$this->assertTrue($this->validator->run(['nickname' => '']));
	}

	// --------------------------------------------------------------------

	public function test_email_rule(): void
	{
		$this->validator->setRules(['mail' => 'required|email']);

		$this->assertTrue($this->validator->run(['mail' => 'ada@example.com']));
		$this->assertFalse($this->validator->run(['mail' => 'not-an-email']));
		$this->assertSame(['The Mail field must be a valid email address.'], $this->validator->errorsFor('mail'));
	}

	// --------------------------------------------------------------------

	public function test_integer_rule(): void
	{
		$this->validator->setRules(['age' => 'required|integer']);

		$this->assertTrue($this->validator->run(['age' => '42']));
		$this->assertTrue($this->validator->run(['age' => -7]));
		$this->assertFalse($this->validator->run(['age' => '4.2']));
		$this->assertFalse($this->validator->run(['age' => 'twelve']));
	}

	// --------------------------------------------------------------------

	public function test_numeric_rule(): void
	{
		$this->validator->setRules(['amount' => 'required|numeric']);

		$this->assertTrue($this->validator->run(['amount' => '3.14']));
		$this->assertFalse($this->validator->run(['amount' => '3,14']));
	}

	// --------------------------------------------------------------------

	public function test_length_rules(): void
	{
		$this->validator->setRules([
			'pin'   => 'required|min_length[4]',
			'code'  => 'required|max_length[3]',
		]);

		$this->assertTrue($this->validator->run(['pin' => '1234', 'code' => 'abc']));
		$this->assertFalse($this->validator->run(['pin' => '123', 'code' => 'abcd']));
		$this->assertSame('The Pin field must be at least 4 characters in length.', $this->validator->errorsFor('pin')[0] ?? '');
		$this->assertSame('The Code field must not exceed 3 characters in length.', $this->validator->errorsFor('code')[0] ?? '');
	}

	// --------------------------------------------------------------------

	public function test_min_and_max_rules(): void
	{
		$this->validator->setRules([
			'qty' => 'required|min[1]|max[10]',
		]);

		$this->assertTrue($this->validator->run(['qty' => 5]));
		$this->assertFalse($this->validator->run(['qty' => 0]));
		$this->assertFalse($this->validator->run(['qty' => 11]));
	}

	// --------------------------------------------------------------------

	public function test_regex_match_rule(): void
	{
		$this->validator->setRules(['slug' => 'required|regex_match[/^[a-z-]+$/]']);

		$this->assertTrue($this->validator->run(['slug' => 'my-post']));
		$this->assertFalse($this->validator->run(['slug' => 'My Post']));
	}

	// --------------------------------------------------------------------

	public function test_in_list_rule_with_param_placeholder(): void
	{
		$this->validator->setRules(['status' => 'required|in_list[draft,published]']);

		$this->assertTrue($this->validator->run(['status' => 'draft']));
		$this->assertFalse($this->validator->run(['status' => 'archived']));
		$this->assertSame(
			'The Status field must be one of: draft,published.',
			$this->validator->errorsFor('status')[0] ?? ''
		);
	}

	// --------------------------------------------------------------------

	public function test_equals_rule_compares_other_field(): void
	{
		$this->validator->setRules([
			'password' => 'required|min_length[8]',
			'confirm'  => 'required|equals[password]',
		]);

		$this->assertTrue($this->validator->run(['password' => 'secret123', 'confirm' => 'secret123']));
		$this->assertFalse($this->validator->run(['password' => 'secret123', 'confirm' => 'nope']));
	}

	// --------------------------------------------------------------------

	public function test_first_failing_rule_wins_per_field(): void
	{
		$this->validator->setRules(['mail' => 'required|email']);

		$this->validator->run(['mail' => 'broken']);

		$errors = $this->validator->errorsFor('mail');
		$this->assertCount(1, $errors);
		$this->assertSame('The Mail field must be a valid email address.', $errors[0]);
	}

	// --------------------------------------------------------------------

	public function test_array_rule_specification(): void
	{
		$this->validator->setRules(['age' => ['required', 'integer', 'max[120]']]);

		$this->assertTrue($this->validator->run(['age' => 30]));
		$this->assertFalse($this->validator->run(['age' => 200]));
	}

	// --------------------------------------------------------------------

	public function test_labels_override_field_names_in_messages(): void
	{
		$this->validator->setRules(['mail' => 'required']);
		$this->validator->setLabels(['mail' => 'Email address']);

		$this->validator->run([]);

		$this->assertSame(['The Email address field is required.'], $this->validator->errorsFor('mail'));
	}

	// --------------------------------------------------------------------

	public function test_custom_message_wins_and_keeps_placeholders(): void
	{
		$this->validator->setRules(['age' => 'required']);
		$this->validator->setMessage('age', 'required', '{field} wajib diisi untuk {param}.');
		$this->validator->setLabels(['age' => 'Umur']);

		$this->validator->run([]);

		$this->assertSame('Umur wajib diisi untuk .', $this->validator->errorsFor('age')[0] ?? '');
	}

	// --------------------------------------------------------------------

	public function test_errors_string_flattens_every_field(): void
	{
		$this->validator->setRules([
			'name' => 'required',
			'mail' => 'required|email',
		]);

		$this->validator->run([]);

		$flat = $this->validator->errorsString(' | ');

		$this->assertStringContainsString('The Name field is required.', $flat);
		$this->assertStringContainsString('The Mail field is required.', $flat);
		$this->assertStringContainsString(' | ', $flat);
	}

	// --------------------------------------------------------------------

	public function test_get_validated_holds_only_passing_fields(): void
	{
		$this->validator->setRules([
			'name' => 'required',
			'age'  => 'integer',
		]);

		$this->validator->run(['name' => 'Ada', 'age' => 'bad', 'extra' => 'ignored']);

		$validated = $this->validator->getValidated();

		$this->assertSame(['name' => 'Ada'], $validated);
		$this->assertArrayNotHasKey('age', $validated);
		$this->assertArrayNotHasKey('extra', $validated);
	}

	// --------------------------------------------------------------------

	public function test_unknown_rule_throws(): void
	{
		$this->validator->setRules(['x' => 'nonsense_rule']);

		$this->expectException(InvalidArgumentException::class);

		$this->validator->run(['x' => 'v']);
	}
}
