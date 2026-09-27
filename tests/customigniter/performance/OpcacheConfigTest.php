<?php
declare(strict_types=1);

use Customigniter\Performance\OpcacheConfig;

class OpcacheConfigTest extends \CI_TestCase
{
    public function test_get_recommended_settings(): void
    {
        $settings = OpcacheConfig::getRecommendedSettings();
        $this->assertArrayHasKey('opcache.enable', $settings);
        $this->assertArrayHasKey('opcache.jit', $settings);
        $this->assertArrayHasKey('opcache.jit_buffer_size', $settings);
        $this->assertEquals(1, $settings['opcache.enable']);
    }

    public function test_generate_ini(): void
    {
        $ini = OpcacheConfig::generateIni();
        $this->assertStringContainsString('opcache.enable', $ini);
        $this->assertStringContainsString('opcache.jit', $ini);
        $this->assertStringContainsString('; Customigniter 3', $ini);
    }

    public function test_jit_modes(): void
    {
        $modes = OpcacheConfig::getJitModes();
        $this->assertArrayHasKey(0, $modes);
        $this->assertArrayHasKey(1205, $modes);
        $this->assertArrayHasKey(1254, $modes);
        $this->assertEquals('Function — compile functions on script load (CRTO 1205)', $modes[1205]);
        $this->assertEquals('Tracing — recommended, traces hot code segments (CRTO 1254)', $modes[1254]);
    }

    public function test_get_current_status(): void
    {
        $status = OpcacheConfig::getCurrentStatus();
        $this->assertArrayHasKey('available', $status);
    }

    public function test_validate_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
        OpcacheConfig::validate();
    }

    public function test_settings_has_positive_memory(): void
    {
        $settings = OpcacheConfig::getRecommendedSettings();
        $this->assertGreaterThan(0, $settings['opcache.memory_consumption']);
    }

    public function test_settings_has_positive_jit_buffer(): void
    {
        $settings = OpcacheConfig::getRecommendedSettings();
        $this->assertGreaterThan(0, $settings['opcache.jit_buffer_size']);
    }
}
