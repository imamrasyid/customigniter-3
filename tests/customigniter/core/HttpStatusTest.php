<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Core\HttpStatus;

class HttpStatusTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_from_code_returns_enum(): void
	{
		$status = HttpStatus::fromCode(200);
		$this->assertEquals(HttpStatus::OK, $status);
	}

	// --------------------------------------------------------------------

	public function test_from_code_invalid_returns_null(): void
	{
		$status = HttpStatus::fromCode(999);
		$this->assertNull($status);
	}

	// --------------------------------------------------------------------

	public function test_phrase(): void
	{
		$this->assertEquals('OK', HttpStatus::OK->phrase());
		$this->assertEquals('Not Found', HttpStatus::NotFound->phrase());
		$this->assertEquals('Internal Server Error', HttpStatus::InternalServerError->phrase());
		$this->assertEquals('Too Many Requests', HttpStatus::TooManyRequests->phrase());
	}

	// --------------------------------------------------------------------

	public function test_is_success(): void
	{
		$this->assertTrue(HttpStatus::OK->isSuccess());
		$this->assertTrue(HttpStatus::Created->isSuccess());
		$this->assertTrue(HttpStatus::NoContent->isSuccess());
		$this->assertFalse(HttpStatus::NotFound->isError() ? false : true);
		$this->assertFalse(HttpStatus::BadRequest->isSuccess());
	}

	// --------------------------------------------------------------------

	public function test_is_redirect(): void
	{
		$this->assertTrue(HttpStatus::MovedPermanently->isRedirect());
		$this->assertTrue(HttpStatus::Found->isRedirect());
		$this->assertTrue(HttpStatus::NotModified->isRedirect());
		$this->assertFalse(HttpStatus::OK->isRedirect());
	}

	// --------------------------------------------------------------------

	public function test_is_client_error(): void
	{
		$this->assertTrue(HttpStatus::BadRequest->isClientError());
		$this->assertTrue(HttpStatus::NotFound->isClientError());
		$this->assertTrue(HttpStatus::Unauthorized->isClientError());
		$this->assertFalse(HttpStatus::OK->isClientError());
		$this->assertFalse(HttpStatus::InternalServerError->isClientError());
	}

	// --------------------------------------------------------------------

	public function test_is_server_error(): void
	{
		$this->assertTrue(HttpStatus::InternalServerError->isServerError());
		$this->assertTrue(HttpStatus::BadGateway->isServerError());
		$this->assertTrue(HttpStatus::ServiceUnavailable->isServerError());
		$this->assertFalse(HttpStatus::NotFound->isServerError());
		$this->assertFalse(HttpStatus::OK->isServerError());
	}

	// --------------------------------------------------------------------

	public function test_is_error(): void
	{
		$this->assertTrue(HttpStatus::NotFound->isError());
		$this->assertTrue(HttpStatus::InternalServerError->isError());
		$this->assertFalse(HttpStatus::OK->isError());
		$this->assertFalse(HttpStatus::Found->isError());
	}

	// --------------------------------------------------------------------

	public function test_value_matches_code(): void
	{
		$this->assertEquals(200, HttpStatus::OK->value);
		$this->assertEquals(404, HttpStatus::NotFound->value);
		$this->assertEquals(500, HttpStatus::InternalServerError->value);
		$this->assertEquals(201, HttpStatus::Created->value);
		$this->assertEquals(204, HttpStatus::NoContent->value);
	}

	// --------------------------------------------------------------------

	/**
	 * @dataProvider provide_valid_statuses
	 */
	public function test_try_from_valid(int $code, HttpStatus $expected): void
	{
		$this->assertSame($expected, HttpStatus::tryFrom($code));
	}

	/**
	 * @return array<int, array{int, HttpStatus}>
	 */
	public static function provide_valid_statuses(): array
	{
		return [
			[200, HttpStatus::OK],
			[404, HttpStatus::NotFound],
			[500, HttpStatus::InternalServerError],
		];
	}

	// --------------------------------------------------------------------

	/**
	 * @dataProvider provide_invalid_statuses
	 */
	public function test_try_from_invalid(int $code): void
	{
		$this->assertNull(HttpStatus::tryFrom($code));
	}

	/**
	 * @return array<int, array{int}>
	 */
	public static function provide_invalid_statuses(): array
	{
		return [[999], [0], [-1]];
	}

	// --------------------------------------------------------------------

	public function test_all_success_codes(): void
	{
		$successCodes = [200, 201, 202, 203, 204, 205, 206];
		foreach ($successCodes as $code)
		{
			$status = HttpStatus::fromCode($code);
			$this->assertNotNull($status, "Code {$code} should exist");
			$this->assertTrue($status->isSuccess(), "Code {$code} should be success");
		}
	}
}
