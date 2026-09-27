<?php
declare(strict_types=1);

use Customigniter\Http\Request;

class RequestTest extends \CI_TestCase
{
    public function test_get_method(): void
    {
        $request = new Request(server: ['REQUEST_METHOD' => 'GET']);
        $this->assertEquals('GET', $request->method());
        $this->assertTrue($request->isGet());
        $this->assertFalse($request->isPost());
    }

    public function test_post_method(): void
    {
        $request = new Request(server: ['REQUEST_METHOD' => 'POST']);
        $this->assertEquals('POST', $request->method());
        $this->assertTrue($request->isPost());
        $this->assertFalse($request->isGet());
    }

    public function test_put_and_delete(): void
    {
        $put    = new Request(server: ['REQUEST_METHOD' => 'PUT']);
        $delete = new Request(server: ['REQUEST_METHOD' => 'DELETE']);
        $this->assertTrue($put->isPut());
        $this->assertTrue($delete->isDelete());
    }

    public function test_query_params(): void
    {
        $request = new Request(query: ['page' => '2', 'sort' => 'name']);
        $this->assertEquals('2', $request->query('page'));
        $this->assertEquals('name', $request->query('sort'));
        $this->assertNull($request->query('missing'));
        $this->assertEquals('default', $request->query('missing', 'default'));
    }

    public function test_post_params(): void
    {
        $request = new Request(post: ['name' => 'John', 'age' => '30']);
        $this->assertEquals('John', $request->post('name'));
        $this->assertEquals('30', $request->post('age'));
    }

    public function test_input_merges_post_and_query(): void
    {
        $request = new Request(
            query: ['id' => '5'],
            post: ['name' => 'Test'],
            server: ['REQUEST_METHOD' => 'POST']
        );
        $this->assertEquals('5', $request->input('id'));
        $this->assertEquals('Test', $request->input('name'));
    }

    public function test_is_ajax(): void
    {
        $ajax = new Request(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertTrue($ajax->isAjax());

        $normal = new Request(server: []);
        $this->assertFalse($normal->isAjax());
    }

    public function test_is_secure(): void
    {
        $https = new Request(server: ['HTTPS' => 'on']);
        $this->assertTrue($https->isSecure());

        $http = new Request(server: ['HTTPS' => 'off']);
        $this->assertFalse($http->isSecure());

        $forwarded = new Request(server: ['HTTP_X_FORWARDED_PROTO' => 'https']);
        $this->assertTrue($forwarded->isSecure());
    }

    public function test_ip(): void
    {
        $request = new Request(server: ['REMOTE_ADDR' => '192.168.1.1']);
        $this->assertEquals('192.168.1.1', $request->ip());

        // Forwarded headers are client-controlled and ignored by default.
        $proxied = new Request(server: ['HTTP_X_FORWARDED_FOR' => '10.0.0.1', 'REMOTE_ADDR' => '192.168.1.1']);
        $this->assertEquals('192.168.1.1', $proxied->ip());

        // Honoured only when the reverse proxy is explicitly trusted.
        $trusted = new Request(
            server: ['HTTP_X_FORWARDED_FOR' => '10.0.0.1, 172.16.0.1', 'REMOTE_ADDR' => '192.168.1.1'],
            trustProxy: true
        );
        $this->assertEquals('10.0.0.1', $trusted->ip());

        // Invalid forwarded values fall back to REMOTE_ADDR.
        $invalid = new Request(
            server: ['HTTP_X_FORWARDED_FOR' => 'not-an-ip', 'REMOTE_ADDR' => '192.168.1.1'],
            trustProxy: true
        );
        $this->assertEquals('192.168.1.1', $invalid->ip());
    }

    public function test_has_and_filled(): void
    {
        $request = new Request(query: ['a' => '1'], post: ['b' => '', 'c' => 'val']);
        $this->assertTrue($request->has('a'));
        $this->assertTrue($request->has('b'));
        $this->assertFalse($request->has('z'));

        $this->assertTrue($request->filled('a'));
        $this->assertFalse($request->filled('b'));
        $this->assertTrue($request->filled('c'));
    }

    public function test_only_and_except(): void
    {
        $request = new Request(query: ['a' => '1', 'b' => '2', 'c' => '3']);
        $this->assertEquals(['a' => '1', 'c' => '3'], $request->only(['a', 'c']));
        $this->assertEquals(['b' => '2'], $request->except(['a', 'c']));
    }

    public function test_accepts(): void
    {
        $request = new Request(server: ['HTTP_ACCEPT' => 'text/html,application/json']);
        $this->assertTrue($request->accepts('json'));
        $this->assertTrue($request->accepts('html'));
        $this->assertFalse($request->accepts('xml'));
    }

    public function test_user_agent(): void
    {
        $request = new Request(server: ['HTTP_USER_AGENT' => 'Mozilla/5.0']);
        $this->assertEquals('Mozilla/5.0', $request->userAgent());
    }

    public function test_default_method_is_get(): void
    {
        $request = new Request(server: []);
        $this->assertEquals('GET', $request->method());
    }
}
