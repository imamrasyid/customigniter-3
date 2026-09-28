<?php
declare(strict_types=1);

namespace Customigniter\Testing;

use PHPUnit\Framework\Assert;

/**
 * Test Response
 *
 * Wraps the captured output of an internal dispatch and offers
 * fluent assertions for status, headers and body content.
 *
 * @package	Customigniter3
 */
final class TestResponse
{
	/**
	 * @param	string				$body
	 * @param	int					$statusCode
	 * @param	array<string, string>	$headers
	 */
	public function __construct(
		private string $body,
		private int $statusCode,
		private array $headers = [],
	) {
	}

	// --------------------------------------------------------------------

	public function getBody(): string
	{
		return $this->body;
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * Read a response header (case-insensitive)
	 */
	public function getHeader(string $name): ?string
	{
		foreach ($this->headers as $header => $value)
		{
			if (strcasecmp($header, $name) === 0)
			{
				return $value;
			}
		}

		return null;
	}

	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Decode the body as JSON
	 */
	public function json(): mixed
	{
		return json_decode($this->body, true);
	}

	// --------------------------------------------------------------------

	public function assertStatus(int $code): self
	{
		Assert::assertSame(
			$code,
			$this->statusCode,
			'Expected status '.$code.' but got '.$this->statusCode.'.'
		);

		return $this;
	}

	public function assertOk(): self
	{
		return $this->assertStatus(200);
	}

	public function assertNotFound(): self
	{
		return $this->assertStatus(404);
	}

	public function assertRedirect(?string $url = null): self
	{
		Assert::assertTrue(
			in_array($this->statusCode, [301, 302, 303, 307, 308], true),
			'Expected a redirect status but got '.$this->statusCode.'.'
		);

		if ($url !== null)
		{
			$location = $this->getHeader('Location');

			Assert::assertNotNull($location, 'Redirect response is missing the Location header.');
			Assert::assertTrue(
				str_contains((string) $location, $url),
				'Location header "'.((string) $location).'" does not contain "'.$url.'".'
			);
		}

		return $this;
	}

	public function assertSee(string $text): self
	{
		Assert::assertTrue(
			str_contains($this->body, $text),
			'Response body does not contain: '.$text
		);

		return $this;
	}

	public function assertDontSee(string $text): self
	{
		Assert::assertFalse(
			str_contains($this->body, $text),
			'Unexpected text found in response body: '.$text
		);

		return $this;
	}

	public function assertJson(): self
	{
		$decoded = json_decode($this->body, true);

		Assert::assertIsArray($decoded, 'Response body is not a valid JSON object/array.');

		return $this;
	}

	/**
	 * Assert a dotted path inside the decoded JSON body
	 *
	 * e.g. assertJsonPath('user.name', 'moby')
	 */
	public function assertJsonPath(string $path, mixed $expected): self
	{
		$decoded = json_decode($this->body, true);

		Assert::assertIsArray($decoded, 'Response body is not a valid JSON object/array.');

		$value = $decoded;

		foreach (explode('.', $path) as $segment)
		{
			Assert::assertTrue(
				is_array($value) && array_key_exists($segment, $value),
				'JSON path "'.$path.'" not found (missing "'.$segment.'").'
			);
			$value = $value[$segment];
		}

		Assert::assertSame($expected, $value, 'JSON path "'.$path.'" does not match.');

		return $this;
	}

	public function assertHeader(string $name, string $value): self
	{
		$actual = $this->getHeader($name);

		Assert::assertNotNull($actual, 'Response header "'.$name.'" is missing.');
		Assert::assertSame($value, $actual, 'Response header "'.$name.'" does not match.');

		return $this;
	}

	public function assertHeaderMissing(string $name): self
	{
		Assert::assertNull(
			$this->getHeader($name),
			'Unexpected response header "'.$name.'" present.'
		);

		return $this;
	}
}
