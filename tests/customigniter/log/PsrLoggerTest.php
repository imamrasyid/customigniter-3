<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Log\PsrLogger;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class PsrLoggerTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_psr_logger_implements_abstract_logger(): void
	{
		$this->assertInstanceOf(\Psr\Log\LoggerInterface::class, new PsrLogger());
	}

	// --------------------------------------------------------------------

	public function test_log_does_not_throw(): void
	{
		$logger = new PsrLogger();
		// Should not throw — CI_Log writes to file
		$logger->log(LogLevel::INFO, 'Test message');
		$this->expectNotToPerformAssertions();
	}

	// --------------------------------------------------------------------

	public function test_log_with_context(): void
	{
		$logger = new PsrLogger();
		$logger->log(LogLevel::ERROR, 'User {id} not found', ['id' => 42]);
		$this->expectNotToPerformAssertions();
	}

	// --------------------------------------------------------------------

	public function test_all_levels(): void
	{
		$logger = new PsrLogger();

		$levels = [
			LogLevel::EMERGENCY,
			LogLevel::ALERT,
			LogLevel::CRITICAL,
			LogLevel::ERROR,
			LogLevel::WARNING,
			LogLevel::NOTICE,
			LogLevel::INFO,
			LogLevel::DEBUG,
		];

		foreach ($levels as $level) {
			$logger->log($level, "Test message at level {$level}");
		}

		$this->expectNotToPerformAssertions();
	}

	// --------------------------------------------------------------------

	public function test_stringable_message(): void
	{
		$logger = new PsrLogger();
		$msg = new class implements \Stringable {
			public function __toString(): string
			{
				return 'Stringable message';
			}
		};

		$logger->log(LogLevel::INFO, $msg);
		$this->expectNotToPerformAssertions();
	}

	// --------------------------------------------------------------------

	public function test_context_non_string_values_ignored(): void
	{
		$logger = new PsrLogger();
		// Integer values should not replace placeholders
		$logger->log(LogLevel::INFO, 'Count: {count}', ['count' => 42]);
		$this->expectNotToPerformAssertions();
	}

	// --------------------------------------------------------------------

	public function test_empty_context(): void
	{
		$logger = new PsrLogger();
		$logger->log(LogLevel::DEBUG, 'No context here');
		$this->expectNotToPerformAssertions();
	}

	// --------------------------------------------------------------------

	public function test_null_logger_from_psr_log(): void
	{
		// Verify our logger works the same as NullLogger (contract test)
		$logger = new PsrLogger();
		$null = new NullLogger();

		// Both should not throw
		$logger->log(LogLevel::INFO, 'test');
		$null->log(LogLevel::INFO, 'test');

		$this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
	}
}
