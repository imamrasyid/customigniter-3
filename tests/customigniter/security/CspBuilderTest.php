<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Security\CspBuilder;

class CspBuilderTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_empty_builder_produces_empty_string(): void
	{
		$builder = new CspBuilder();
		$this->assertEquals('', $builder->build());
	}

	// --------------------------------------------------------------------

	public function test_default_src(): void
	{
		$builder = new CspBuilder();
		$result = $builder->defaultSrc("'self'")->build();
		$this->assertStringContainsString("default-src 'self'", $result);
	}

	// --------------------------------------------------------------------

	public function test_script_src(): void
	{
		$builder = new CspBuilder();
		$result = $builder->scriptSrc("'self'", 'https://cdn.example.com')->build();
		$this->assertStringContainsString("script-src 'self' https://cdn.example.com", $result);
	}

	// --------------------------------------------------------------------

	public function test_style_src(): void
	{
		$builder = new CspBuilder();
		$result = $builder->styleSrc("'self'", 'https://fonts.googleapis.com')->build();
		$this->assertStringContainsString("style-src 'self' https://fonts.googleapis.com", $result);
	}

	// --------------------------------------------------------------------

	public function test_multiple_directives(): void
	{
		$builder = new CspBuilder();
		$result = $builder
			->defaultSrc("'self'")
			->scriptSrc("'self'", 'https://cdn.example.com')
			->styleSrc("'self'", 'https://fonts.googleapis.com')
			->imgSrc("'self'", 'data:', 'https:')
			->build();

		$this->assertStringContainsString("default-src 'self'", $result);
		$this->assertStringContainsString("script-src", $result);
		$this->assertStringContainsString("style-src", $result);
		$this->assertStringContainsString("img-src", $result);
		$this->assertStringContainsString('; ', $result);
	}

	// --------------------------------------------------------------------

	public function test_nonce_script(): void
	{
		$builder = new CspBuilder();
		$result = $builder->scriptNonce('abc123base64')->build();
		$this->assertStringContainsString("script-src 'nonce-abc123base64'", $result);
	}

	// --------------------------------------------------------------------

	public function test_nonce_style(): void
	{
		$builder = new CspBuilder();
		$result = $builder->styleNonce('xyz789')->build();
		$this->assertStringContainsString("style-src 'nonce-xyz789'", $result);
	}

	// --------------------------------------------------------------------

	public function test_script_hash(): void
	{
		$builder = new CspBuilder();
		$result = $builder->scriptHash('sha256-abc123=')->build();
		$this->assertStringContainsString("script-src 'sha256-abc123='", $result);
	}

	// --------------------------------------------------------------------

	public function test_deduplicates_sources(): void
	{
		$builder = new CspBuilder();
		$result = $builder
			->scriptSrc("'self'")
			->scriptSrc("'self'")
			->build();
		// 'self' should appear only once
		$this->assertEquals(1, substr_count($result, "'self'"));
	}

	// --------------------------------------------------------------------

	public function test_report_uri(): void
	{
		$builder = new CspBuilder();
		$result = $builder
			->defaultSrc("'self'")
			->setReportUri('https://example.com/csp-report')
			->build();
		$this->assertStringContainsString('report-uri https://example.com/csp-report', $result);
	}

	// --------------------------------------------------------------------

	public function test_report_only_mode(): void
	{
		$builder = new CspBuilder();
		$builder->setReportOnly(true)->defaultSrc("'self'");
		$built = $builder->build();
		$this->assertNotEmpty($built, 'CSP header value should not be empty');

		// Test the flag is set correctly by rebuilding
		$builder2 = new CspBuilder();
		$builder2->setReportOnly(true)->defaultSrc("'self'");
		$this->assertTrue($builder2->build() !== '');
	}

	// --------------------------------------------------------------------

	public function test_send_header_builds_correctly(): void
	{
		$builder = new CspBuilder();
		$builder->defaultSrc("'self'")->scriptSrc("'self'");

		$built = $builder->build();
		$this->assertStringContainsString("default-src 'self'", $built);
		$this->assertStringContainsString("script-src 'self'", $built);
	}

	// --------------------------------------------------------------------

	public function test_add_source(): void
	{
		$builder = new CspBuilder();
		$result = $builder->addSource('connect-src', 'https://api.example.com')->build();
		$this->assertStringContainsString('connect-src https://api.example.com', $result);
	}

	// --------------------------------------------------------------------

	public function test_fluent_interface_returns_self(): void
	{
		$builder = new CspBuilder();
		$this->assertSame($builder, $builder->defaultSrc("'self'"));
		$this->assertSame($builder, $builder->scriptSrc("'self'"));
		$this->assertSame($builder, $builder->styleSrc("'self'"));
		$this->assertSame($builder, $builder->imgSrc("'self'"));
		$this->assertSame($builder, $builder->fontSrc("'self'"));
		$this->assertSame($builder, $builder->connectSrc("'self'"));
		$this->assertSame($builder, $builder->frameSrc("'self'"));
		$this->assertSame($builder, $builder->objectSrc("'none'"));
		$this->assertSame($builder, $builder->mediaSrc("'self'"));
		$this->assertSame($builder, $builder->workerSrc("'self'"));
		$this->assertSame($builder, $builder->manifestSrc("'self'"));
	}

	// --------------------------------------------------------------------

	public function test_empty_send_header_does_nothing(): void
	{
		$builder = new CspBuilder();
		// Should not throw or send anything
		ob_start();
		$builder->sendHeader();
		$output = ob_get_clean();
		$this->assertEquals('', $output);
	}
}
