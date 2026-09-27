<?php
declare(strict_types=1);

use Customigniter\Security\RateLimiter;

class RateLimiterTest extends \CI_TestCase
{
    private string $tmpDir;

    public function set_up(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ci3_rl_test_' . uniqid();
    }

    public function tear_down(): void
    {
        if (is_dir($this->tmpDir)) {
            array_map('unlink', glob($this->tmpDir . '/*.json') ?: []);
            @rmdir($this->tmpDir);
        }
    }

    private function createLimiter(int $max = 5, int $decay = 1): RateLimiter
    {
        return new RateLimiter($this->tmpDir, $max, $decay);
    }

    public function test_allows_under_limit()
    {
        $limiter = $this->createLimiter(3);
        $this->assertTrue($limiter->attempt('test'));
        $this->assertEquals(1, $limiter->attempts('test'));
    }

    public function test_blocks_after_limit()
    {
        $limiter = $this->createLimiter(2);
        $this->assertTrue($limiter->attempt('test'));
        $this->assertTrue($limiter->attempt('test'));
        $this->assertFalse($limiter->attempt('test'));
    }

    public function test_remaining_count()
    {
        $limiter = $this->createLimiter(5);
        $this->assertEquals(5, $limiter->remaining('test'));
        $limiter->attempt('test');
        $limiter->attempt('test');
        $this->assertEquals(3, $limiter->remaining('test'));
    }

    public function test_reset()
    {
        $limiter = $this->createLimiter(2);
        $limiter->attempt('test');
        $limiter->attempt('test');
        $this->assertFalse($limiter->attempt('test'));

        $limiter->reset('test');
        $this->assertTrue($limiter->attempt('test'));
        $this->assertEquals(1, $limiter->attempts('test'));
    }

    public function test_separate_keys()
    {
        $limiter = $this->createLimiter(1);
        $this->assertTrue($limiter->attempt('a'));
        $this->assertFalse($limiter->attempt('a'));
        $this->assertTrue($limiter->attempt('b'));
    }

    public function test_custom_max_per_attempt()
    {
        $limiter = $this->createLimiter(10);
        $this->assertTrue($limiter->attempt('test', 1));
        $this->assertFalse($limiter->attempt('test', 1));
    }

    public function test_fluent_config()
    {
        $limiter = $this->createLimiter();
        $result  = $limiter->setMaxAttempts(100)->setDecayMinutes(5);
        $this->assertSame($limiter, $result);
    }

    public function test_initial_zero_attempts()
    {
        $limiter = $this->createLimiter();
        $this->assertEquals(0, $limiter->attempts('fresh_key'));
    }
}
