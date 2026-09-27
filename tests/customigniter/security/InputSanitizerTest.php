<?php
declare(strict_types=1);

use Customigniter\Security\InputSanitizer;

class InputSanitizerTest extends \CI_TestCase
{
    public function test_string_strips_tags()
    {
        $this->assertEquals('hello', InputSanitizer::string('<b>hello</b>'));
    }

    public function test_string_does_not_html_escape()
    {
        // Escaping happens at the output layer; input is stored raw
        // to avoid double-encoding when html_escape() runs on render.
        $this->assertEquals('&', InputSanitizer::string('&'));
        $this->assertEquals('"', InputSanitizer::string('"'));
    }

    public function test_html_escapes_explicitly()
    {
        $this->assertEquals('&amp;', InputSanitizer::html('&'));
        $this->assertEquals('&quot;', InputSanitizer::html('"'));
    }

    public function test_array_preserves_special_chars_in_keys()
    {
        $clean = InputSanitizer::array(
            ['a&b' => '<b>x</b>', "bad\x00key" => 'y'],
            [InputSanitizer::class, 'string']
        );

        $this->assertArrayHasKey('a&b', $clean);
        $this->assertEquals('x', $clean['a&b']);
        $this->assertArrayHasKey('badkey', $clean);
    }

    public function test_string_trims()
    {
        $this->assertEquals('hello', InputSanitizer::string('  hello  '));
    }

    public function test_string_max_length()
    {
        $this->assertEquals('hel', InputSanitizer::string('hello', 3));
        $this->assertEquals('hello', InputSanitizer::string('hello', 10));
    }

    public function test_email()
    {
        $this->assertEquals('test@example.com', InputSanitizer::email('test@example.com'));
        $this->assertEquals('', InputSanitizer::email('not-an-email'));
    }

    public function test_int()
    {
        $this->assertIsInt(InputSanitizer::int('123abc'));
        $this->assertEquals(123, InputSanitizer::int('123abc'));
        $this->assertEquals(-5, InputSanitizer::int('-5xyz'));
    }

    public function test_float()
    {
        $this->assertEquals(3.14, InputSanitizer::float('3.14abc'));
        $this->assertIsFloat(InputSanitizer::float('1.5'));
    }

    public function test_bool()
    {
        $this->assertTrue(InputSanitizer::bool('1'));
        $this->assertTrue(InputSanitizer::bool('true'));
        $this->assertFalse(InputSanitizer::bool('0'));
        $this->assertFalse(InputSanitizer::bool('false'));
    }

    public function test_url_valid()
    {
        $this->assertEquals('https://example.com', InputSanitizer::url('https://example.com'));
    }

    public function test_url_invalid()
    {
        $this->assertEquals('', InputSanitizer::url('not a url'));
    }

    public function test_alphanum()
    {
        $this->assertEquals('abc123', InputSanitizer::alphanum('abc-123!@#'));
    }

    public function test_filename()
    {
        $this->assertEquals('passwd', InputSanitizer::filename('../../../etc/passwd'));
        $this->assertEquals('image.png', InputSanitizer::filename('image.png'));
    }
}
