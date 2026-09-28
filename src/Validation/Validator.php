<?php
declare(strict_types=1);

namespace Customigniter\Validation;

/**
 * Data validation engine
 *
 * Rules are declared per field as pipe strings ('required|email') or
 * rule lists. Non-required rules only run when the field is present, so
 * optional fields stay optional. The first failing rule per field
 * produces the error message.
 *
 * Supported rules: required, email, integer, numeric, min_length[n],
 * max_length[n], min[n], max[n], regex_match[pattern], in_list[a,b,c],
 * equals[field].
 *
 * @package	Customigniter3
 */
class Validator
{
	/**
	 * Normalised rules, field => list of 'name[param]' strings
	 *
	 * @var	array<string, array<int, string>>
	 */
	private array $rules = [];

	/**
	 * Human labels for fields
	 *
	 * @var	array<string, string>
	 */
	private array $labels = [];

	/**
	 * Custom messages keyed by 'field.rule'
	 *
	 * @var	array<string, string>
	 */
	private array $messages = [];

	/**
	 * Error messages of the last run, field => list of messages
	 *
	 * @var	array<string, array<int, string>>
	 */
	private array $errors = [];

	/**
	 * Fields that passed every rule in the last run
	 *
	 * @var	array<string, mixed>
	 */
	private array $validated = [];

	// --------------------------------------------------------------------

	/**
	 * Define validation rules
	 *
	 * @param	array<string, string|array<int, string>>	$rules	Field => pipe string or rule list
	 * @return	static
	 */
	public function setRules(array $rules): static
	{
		$this->rules = [];

		foreach ($rules as $field => $ruleSet)
		{
			$field = (string) $field;
			$specs = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
			$clean = [];

			foreach ($specs as $spec)
			{
				$spec = trim((string) $spec);

				if ($spec !== '')
				{
					$clean[] = $spec;
				}
			}

			$this->rules[$field] = $clean;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set display labels for fields
	 *
	 * @param	array<string, string>	$labels
	 * @return	static
	 */
	public function setLabels(array $labels): static
	{
		foreach ($labels as $field => $label)
		{
			$this->labels[(string) $field] = (string) $label;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Override the message for one field rule
	 *
	 * The placeholders {field} and {param} are replaced when rendering.
	 *
	 * @param	string	$field	Field name
	 * @param	string	$rule	Rule name
	 * @param	string	$message	Message template
	 * @return	static
	 */
	public function setMessage(string $field, string $rule, string $message): static
	{
		$this->messages[$field.'.'.$rule] = $message;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate the data against the configured rules
	 *
	 * @param	array<string, mixed>	$data
	 * @return	bool	True when every field passed
	 */
	public function run(array $data): bool
	{
		$this->errors = [];
		$this->validated = [];

		foreach ($this->rules as $field => $specs)
		{
			$value = $data[$field] ?? null;
			$failed = false;

			foreach ($specs as $spec)
			{
				[$rule, $param] = $this->parseRule($spec);

				$result = $this->check($rule, $param, $value, $data);

				if ($result === null)
				{
					continue;
				}

				$this->errors[$field][] = $this->format($field, $rule, $param);
				$failed = true;

				break;
			}

			if ( ! $failed && ($this->isRequired($this->rules[$field]) || ($value !== null && $value !== '')))
			{
				$this->validated[$field] = $value;
			}
		}

		return $this->errors === [];
	}

	// --------------------------------------------------------------------

	/**
	 * Errors of the last run, grouped by field
	 *
	 * @return	array<string, array<int, string>>
	 */
	public function errors(): array
	{
		return $this->errors;
	}

	// --------------------------------------------------------------------

	/**
	 * Messages for a single field
	 *
	 * @param	string	$field
	 * @return	array<int, string>
	 */
	public function errorsFor(string $field): array
	{
		return $this->errors[$field] ?? [];
	}

	// --------------------------------------------------------------------

	/**
	 * All error messages as one string
	 *
	 * @param	string	$separator
	 * @return	string
	 */
	public function errorsString(string $separator = "\n"): string
	{
		$flat = [];

		foreach ($this->errors as $fieldErrors)
		{
			foreach ($fieldErrors as $message)
			{
				$flat[] = $message;
			}
		}

		return implode($separator, $flat);
	}

	// --------------------------------------------------------------------

	/**
	 * Data that passed validation in the last run
	 *
	 * @return	array<string, mixed>
	 */
	public function getValidated(): array
	{
		return $this->validated;
	}

	// --------------------------------------------------------------------

	/**
	 * Split a rule specification into name and parameter
	 *
	 * @param	string	$spec
	 * @return	array{0: string, 1: string}
	 */
	private function parseRule(string $spec): array
	{
		if (preg_match('/^([a-zA-Z_]+)\[(.*)\]$/', $spec, $matches) === 1)
		{
			return [$matches[1], $matches[2]];
		}

		return [$spec, ''];
	}

	// --------------------------------------------------------------------

	/**
	 * Apply one rule to a value
	 *
	 * @param	string					$rule	Rule name
	 * @param	string					$param	Rule parameter
	 * @param	mixed					$value	Field value
	 * @param	array<string, mixed>	$data	All input data (for equals)
	 * @return	string|null	Empty string on failure, null when passed
	 */
	private function check(string $rule, string $param, mixed $value, array $data): ?string
	{
		$present = $value !== null && $value !== '';

		switch ($rule)
		{
			case 'required':
				if ($value === null || $value === '' || $value === [])
				{
					return '';
				}

				return null;

			case 'email':
				return $present && ! filter_var($value, FILTER_VALIDATE_EMAIL) ? '' : null;

			case 'integer':
				if ( ! $present)
				{
					return null;
				}

				if (is_int($value))
				{
					return null;
				}

				return is_string($value) && preg_match('/^-?\d+$/', $value) === 1 ? null : '';

			case 'numeric':
				return $present && ! is_numeric($value) ? '' : null;

			case 'min_length':
				return $present && is_string($value) && mb_strlen($value) < (int) $param ? '' : null;

			case 'max_length':
				return $present && is_string($value) && mb_strlen($value) > (int) $param ? '' : null;

			case 'min':
				return $present && is_numeric($value) && (float) $value < (float) $param ? '' : null;

			case 'max':
				return $present && is_numeric($value) && (float) $value > (float) $param ? '' : null;

			case 'regex_match':
				if ( ! $present)
				{
					return null;
				}

				if ( ! is_scalar($value) || $param === '' || @preg_match($param, (string) $value) !== 1)
				{
					return '';
				}

				return null;

			case 'in_list':
				if ( ! $present)
				{
					return null;
				}

				if ( ! is_scalar($value))
				{
					return '';
				}

				return in_array((string) $value, explode(',', $param), true) ? null : '';

			case 'equals':
				$other = $data[$param] ?? null;

				return $present && $value !== $other ? '' : null;

			default:
				throw new \InvalidArgumentException('Unknown validation rule: '.$rule);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Whether any configured spec for a field is the required rule
	 *
	 * @param	array<int, string>	$specs
	 * @return	bool
	 */
	private function isRequired(array $specs): bool
	{
		foreach ($specs as $spec)
		{
			if ($spec === 'required')
			{
				return true;
			}
		}

		return false;
	}

	// --------------------------------------------------------------------

	/**
	 * Build the error message for a failed rule
	 *
	 * @param	string	$field
	 * @param	string	$rule
	 * @param	string	$param
	 * @return	string
	 */
	private function format(string $field, string $rule, string $param): string
	{
		$message = $this->messages[$field.'.'.$rule] ?? $this->defaultMessage($rule);

		$label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));

		return str_replace(['{field}', '{param}'], [$label, $param], $message);
	}

	// --------------------------------------------------------------------

	/**
	 * Built-in message for a rule
	 *
	 * @param	string	$rule
	 * @return	string
	 */
	private function defaultMessage(string $rule): string
	{
		$messages = [
			'required'     => 'The {field} field is required.',
			'email'        => 'The {field} field must be a valid email address.',
			'integer'      => 'The {field} field must be an integer.',
			'numeric'      => 'The {field} field must contain only numbers.',
			'min_length'   => 'The {field} field must be at least {param} characters in length.',
			'max_length'   => 'The {field} field must not exceed {param} characters in length.',
			'min'          => 'The {field} field must be at least {param}.',
			'max'          => 'The {field} field must not be greater than {param}.',
			'regex_match'  => 'The {field} field is not in the correct format.',
			'in_list'      => 'The {field} field must be one of: {param}.',
			'equals'       => 'The {field} field must match {param}.',
		];

		return $messages[$rule] ?? 'The {field} field is invalid.';
	}
}
