<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Security\PasswordHasher;

class PasswordHasherTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_hash_returns_string()
	{
		$hasher = new PasswordHasher();
		$hash = $hasher->hash('secret123');
		$this->assertIsString($hash);
		$this->assertNotEmpty($hash);
	}

	// --------------------------------------------------------------------

	public function test_hash_different_each_time()
	{
		$hasher = new PasswordHasher();
		$hash1 = $hasher->hash('secret123');
		$hash2 = $hasher->hash('secret123');
		$this->assertNotEquals($hash1, $hash2, 'Hashes should be unique per call');
	}

	// --------------------------------------------------------------------

	public function test_verify_correct_password()
	{
		$hasher = new PasswordHasher();
		$hash = $hasher->hash('secret123');
		$this->assertTrue($hasher->verify('secret123', $hash));
	}

	// --------------------------------------------------------------------

	public function test_verify_wrong_password()
	{
		$hasher = new PasswordHasher();
		$hash = $hasher->hash('secret123');
		$this->assertFalse($hasher->verify('wrongpassword', $hash));
	}

	// --------------------------------------------------------------------

	public function test_verify_empty_password()
	{
		$hasher = new PasswordHasher();
		$hash = $hasher->hash('secret123');
		$this->assertFalse($hasher->verify('', $hash));
	}

	// --------------------------------------------------------------------

	public function test_hash_with_bcrypt()
	{
		if ( ! PasswordHasher::isAlgorithmAvailable('bcrypt'))
		{
			$this->markTestSkipped('bcrypt not available on this system');
		}

		$hasher = new PasswordHasher();
		$hash = $hasher->hash('secret123', 'bcrypt');
		$info = $hasher->info($hash);
		$this->assertEquals('bcrypt', $info['algoName']);
	}

	// --------------------------------------------------------------------

	public function test_hash_with_argon2id_if_available()
	{
		if ( ! PasswordHasher::isAlgorithmAvailable('argon2id'))
		{
			$this->markTestSkipped('argon2id not available on this system');
		}

		$hasher = new PasswordHasher();
		$hash = $hasher->hash('secret123', 'argon2id');
		$info = $hasher->info($hash);
		$this->assertEquals('argon2id', $info['algoName']);
	}

	// --------------------------------------------------------------------

	public function test_hash_with_auto_selects_best()
	{
		$hasher = new PasswordHasher();
		$hash = $hasher->hash('secret123', 'auto');
		$this->assertNotEmpty($hash);
		$this->assertTrue($hasher->verify('secret123', $hash));
	}

	// --------------------------------------------------------------------

	public function test_needs_rehash_returns_bool()
	{
		$hasher = new PasswordHasher();
		$hash = $hasher->hash('secret123');
		$this->assertIsBool($hasher->needsRehash($hash));
	}

	// --------------------------------------------------------------------

	public function test_info_returns_array()
	{
		$hasher = new PasswordHasher();
		$hash = $hasher->hash('secret123');
		$info = $hasher->info($hash);
		$this->assertArrayHasKey('algo', $info);
		$this->assertArrayHasKey('algoName', $info);
		$this->assertArrayHasKey('options', $info);
	}

	// --------------------------------------------------------------------

	public function test_is_algorithm_available_static()
	{
		$this->assertTrue(PasswordHasher::isAlgorithmAvailable('auto'));
		$this->assertFalse(PasswordHasher::isAlgorithmAvailable('invalid_algo'));
		// At least one algorithm should be available
		$hasAny = PasswordHasher::isAlgorithmAvailable('argon2id')
			|| PasswordHasher::isAlgorithmAvailable('argon2i')
			|| PasswordHasher::isAlgorithmAvailable('bcrypt');
		$this->assertTrue($hasAny, 'At least one hashing algorithm should be available');
	}

	// --------------------------------------------------------------------

	public function test_invalid_algorithm_throws_exception()
	{
		$this->expectException(\RuntimeException::class);
		$hasher = new PasswordHasher();
		$hasher->hash('secret123', 'invalid_algo');
	}

	// --------------------------------------------------------------------

	public function test_preferred_algorithm_in_constructor()
	{
		$best = (new PasswordHasher())->getBestAvailableAlgorithm();
		$hasher = new PasswordHasher($best);
		$hash = $hasher->hash('secret123');
		$info = $hasher->info($hash);
		$this->assertEquals($best, $info['algoName']);
	}

	// --------------------------------------------------------------------

	public function test_unicode_password()
	{
		$hasher = new PasswordHasher();
		$password = 'kata_sandi_kata_sandi';
		$hash = $hasher->hash($password);
		$this->assertTrue($hasher->verify($password, $hash));
	}

	// --------------------------------------------------------------------

	public function test_long_password()
	{
		$hasher = new PasswordHasher();
		$password = str_repeat('a', 72);
		$hash = $hasher->hash($password);
		$this->assertTrue($hasher->verify($password, $hash));
	}
}
