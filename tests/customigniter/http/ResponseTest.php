<?php
declare(strict_types=1);

use Customigniter\Http\Response;

class ResponseTest extends \CI_TestCase
{
    public function test_default_status()
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_status_fluent()
    {
        $response = new Response();
        $result = $response->status(404);
        $this->assertSame($response, $result);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_header_fluent()
    {
        $response = new Response();
        $result = $response->header('X-Custom', 'value');
        $this->assertSame($response, $result);
        $this->assertEquals(['X-Custom' => 'value'], $response->getHeaders());
    }

    public function test_headers_bulk()
    {
        $response = new Response();
        $response->headers(['A' => '1', 'B' => '2']);
        $this->assertEquals(['A' => '1', 'B' => '2'], $response->getHeaders());
    }

    public function test_body_fluent()
    {
        $response = new Response();
        $result = $response->body('hello');
        $this->assertSame($response, $result);
        $this->assertEquals('hello', $response->getBody());
    }

    public function test_json()
    {
        $response = new Response();
        $response->json(['key' => 'value']);
        $this->assertEquals('{"key":"value"}', $response->getBody());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeaders()['Content-Type']);
    }

    public function test_html()
    {
        $response = new Response();
        $response->html('<h1>Hello</h1>');
        $this->assertEquals('<h1>Hello</h1>', $response->getBody());
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeaders()['Content-Type']);
    }

    public function test_text()
    {
        $response = new Response();
        $response->text('plain');
        $this->assertEquals('plain', $response->getBody());
        $this->assertEquals('text/plain; charset=UTF-8', $response->getHeaders()['Content-Type']);
    }

    public function test_redirect()
    {
        $response = new Response();
        $response->redirect('/login', 301);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaders()['Location']);
    }

    public function test_cache()
    {
        $response = new Response();
        $response->cache(3600);
        $this->assertStringContainsString('max-age=3600', $response->getHeaders()['Cache-Control']);
        $this->assertStringContainsString('public', $response->getHeaders()['Cache-Control']);
    }

    public function test_private_cache()
    {
        $response = new Response();
        $response->cache(600, false);
        $this->assertStringContainsString('private', $response->getHeaders()['Cache-Control']);
    }

    public function test_no_cache()
    {
        $response = new Response();
        $response->noCache();
        $this->assertStringContainsString('no-store', $response->getHeaders()['Cache-Control']);
        $this->assertEquals('no-cache', $response->getHeaders()['Pragma']);
    }

    public function test_no_content()
    {
        $response = new Response();
        $response->noContent();
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', $response->getBody());
    }

    public function test_fluent_chaining()
    {
        $response = new Response();
        $result = $response
            ->status(201)
            ->header('X-Request-Id', 'abc')
            ->json(['created' => true]);

        $this->assertSame($response, $result);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('abc', $response->getHeaders()['X-Request-Id']);
        $this->assertEquals('{"created":true}', $response->getBody());
    }
}
