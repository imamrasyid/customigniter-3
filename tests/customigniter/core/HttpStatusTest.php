<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Core\HttpStatus;

class HttpStatusTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_from_code_returns_enum()
	{
		$status = HttpStatus::fromCode(200);
		$this->assertEquals(HttpStatus::OK, $status);
	}

	// --------------------------------------------------------------------

	public function test_from_code_invalid_returns_null()
	{
		$status = HttpStatus::fromCode(999);
		$this->assertNull($status);
	}

	// --------------------------------------------------------------------

	public function test_phrase()
	{
		$this->assertEquals('OK', HttpStatus::OK->phrase());
		$this->assertEquals('Not Found', HttpStatus::NotFound->phrase());
		$this->assertEquals('Internal Server Error', HttpStatus::InternalServerError->phrase());
		$this->assertEquals('Too Many Requests', HttpStatus::TooManyRequests->phrase());
	}

	// --------------------------------------------------------------------

	public function test_is_success()
	{
		$this->assertTrue(HttpStatus::OK->isSuccess());
		$this->assertTrue(HttpStatus::Created->isSuccess());
		$this->assertTrue(HttpStatus::NoContent->isSuccess());
		$this->assertFalse(HttpStatus::NotFound->isError() ? false : true);
		$this->assertFalse(HttpStatus::BadRequest->isSuccess());
	}

	// --------------------------------------------------------------------

	public function test_is_redirect()
	{
		$this->assertTrue(HttpStatus::MovedPermanently->isRedirect());
		$this->assertTrue(HttpStatus::Found->isRedirect());
		$this->assertTrue(HttpStatus::NotModified->isRedirect());
		$this->assertFalse(HttpStatus::OK->isRedirect());
	}

	// --------------------------------------------------------------------

	public function test_is_client_error()
	{
		$this->assertTrue(HttpStatus::BadRequest->isClientError());
		$this->assertTrue(HttpStatus::NotFound->isClientError());
		$this->assertTrue(HttpStatus::Unauthorized->isClientError());
		$this->assertFalse(HttpStatus::OK->isClientError());
		$this->assertFalse(HttpStatus::InternalServerError->isClientError());
	}

	// --------------------------------------------------------------------

	public function test_is_server_error()
	{
		$this->assertTrue(HttpStatus::InternalServerError->isServerError());
		$this->assertTrue(HttpStatus::BadGateway->isServerError());
		$this->assertTrue(HttpStatus::ServiceUnavailable->isServerError());
		$this->assertFalse(HttpStatus::NotFound->isServerError());
		$this->assertFalse(HttpStatus::OK->isServerError());
	}

	// --------------------------------------------------------------------

	public function test_is_error()
	{
		$this->assertTrue(HttpStatus::NotFound->isError());
		$this->assertTrue(HttpStatus::InternalServerError->isError());
		$this->assertFalse(HttpStatus::OK->isError());
		$this->assertFalse(HttpStatus::Found->isError());
	}

	// --------------------------------------------------------------------

	public function test_value_matches_code()
	{
		$this->assertEquals(200, HttpStatus::OK->value);
		$this->assertEquals(404, HttpStatus::NotFound->value);
		$this->assertEquals(500, HttpStatus::InternalServerError->value);
		$this->assertEquals(201, HttpStatus::Created->value);
		$this->assertEquals(204, HttpStatus::NoContent->value);
	}

	// --------------------------------------------------------------------

	public function test_try_from_valid()
	{
		$this->assertNotNull(HttpStatus::tryFrom(200));
		$this->assertNotNull(HttpStatus::tryFrom(404));
		$this->assertNotNull(HttpStatus::tryFrom(500));
	}

	// --------------------------------------------------------------------

	public function test_try_from_invalid()
	{
		$this->assertNull(HttpStatus::tryFrom(999));
		$this->assertNull(HttpStatus::tryFrom(0));
		$this->assertNull(HttpStatus::tryFrom(-1));
	}

	// --------------------------------------------------------------------

	public function test_all_success_codes()
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
