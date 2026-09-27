<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Customigniter\Database\Migration\TableBuilder;

class TableBuilderTest extends TestCase
{
	// --------------------------------------------------------------------

	public function test_id_adds_auto_increment_primary_key(): void
	{
		$builder = new TableBuilder('users');
		$builder->id();

		$fields = $builder->getFields();
		$this->assertArrayHasKey('id', $fields);
		$this->assertEquals('INT', $fields['id']['type']);
		$this->assertTrue($fields['id']['unsigned']);
		$this->assertTrue($fields['id']['auto_increment']);
		$this->assertEquals(['id'], $builder->getPrimaryKeys());
	}

	// --------------------------------------------------------------------

	public function test_id_custom_name(): void
	{
		$builder = new TableBuilder('users');
		$builder->id('user_id');

		$fields = $builder->getFields();
		$this->assertArrayHasKey('user_id', $fields);
		$this->assertEquals(['user_id'], $builder->getPrimaryKeys());
	}

	// --------------------------------------------------------------------

	public function test_string_column(): void
	{
		$builder = new TableBuilder('users');
		$builder->string('email', 191);

		$fields = $builder->getFields();
		$this->assertEquals('VARCHAR', $fields['email']['type']);
		$this->assertEquals(191, $fields['email']['constraint']);
	}

	// --------------------------------------------------------------------

	public function test_text_column(): void
	{
		$builder = new TableBuilder('posts');
		$builder->text('body', true);

		$fields = $builder->getFields();
		$this->assertEquals('TEXT', $fields['body']['type']);
		$this->assertTrue($fields['body']['null']);
	}

	// --------------------------------------------------------------------

	public function test_integer_column(): void
	{
		$builder = new TableBuilder('counters');
		$builder->integer('count', 10, true);

		$fields = $builder->getFields();
		$this->assertEquals('INT', $fields['count']['type']);
		$this->assertEquals(10, $fields['count']['constraint']);
		$this->assertTrue($fields['count']['unsigned']);
	}

	// --------------------------------------------------------------------

	public function test_bigInteger_column(): void
	{
		$builder = new TableBuilder('logs');
		$builder->bigInteger('event_id');

		$fields = $builder->getFields();
		$this->assertEquals('BIGINT', $fields['event_id']['type']);
	}

	// --------------------------------------------------------------------

	public function test_boolean_column(): void
	{
		$builder = new TableBuilder('settings');
		$builder->boolean('active', true);

		$fields = $builder->getFields();
		$this->assertEquals('TINYINT', $fields['active']['type']);
		$this->assertEquals(1, $fields['active']['default']);
	}

	// --------------------------------------------------------------------

	public function test_datetime_column(): void
	{
		$builder = new TableBuilder('events');
		$builder->datetime('starts_at', true);

		$fields = $builder->getFields();
		$this->assertEquals('DATETIME', $fields['starts_at']['type']);
		$this->assertTrue($fields['starts_at']['null']);
	}

	// --------------------------------------------------------------------

	public function test_timestamp_column(): void
	{
		$builder = new TableBuilder('posts');
		$builder->timestamp('created_at');

		$fields = $builder->getFields();
		$this->assertEquals('TIMESTAMP', $fields['created_at']['type']);
		$this->assertEquals('CURRENT_TIMESTAMP', $fields['created_at']['default']);
	}

	// --------------------------------------------------------------------

	public function test_json_column(): void
	{
		$builder = new TableBuilder('configs');
		$builder->json('data', true);

		$fields = $builder->getFields();
		$this->assertEquals('JSON', $fields['data']['type']);
		$this->assertTrue($fields['data']['null']);
	}

	// --------------------------------------------------------------------

	public function test_float_column(): void
	{
		$builder = new TableBuilder('products');
		$builder->float('price', 4);

		$fields = $builder->getFields();
		$this->assertEquals('FLOAT', $fields['price']['type']);
		$this->assertEquals(4, $fields['price']['constraint']);
	}

	// --------------------------------------------------------------------

	public function test_decimal_column(): void
	{
		$builder = new TableBuilder('products');
		$builder->decimal('price', 10, 2);

		$fields = $builder->getFields();
		$this->assertEquals('DECIMAL', $fields['price']['type']);
		$this->assertEquals('10,2', $fields['price']['constraint']);
	}

	// --------------------------------------------------------------------

	public function test_unique_key(): void
	{
		$builder = new TableBuilder('users');
		$builder->string('email')->unique('email');

		$this->assertEquals(['email'], $builder->getUniqueKeys());
	}

	// --------------------------------------------------------------------

	public function test_index_key(): void
	{
		$builder = new TableBuilder('posts');
		$builder->string('status')->index('status');

		$this->assertEquals(['status'], $builder->getIndexes());
	}

	// --------------------------------------------------------------------

	public function test_default_value(): void
	{
		$builder = new TableBuilder('settings');
		$builder->string('theme');
		$builder->default('theme', 'dark');

		$fields = $builder->getFields();
		$this->assertEquals('dark', $fields['theme']['default']);
	}

	// --------------------------------------------------------------------

	public function test_nullable(): void
	{
		$builder = new TableBuilder('users');
		$builder->string('bio');
		$builder->nullable('bio');

		$fields = $builder->getFields();
		$this->assertTrue($fields['bio']['null']);
	}

	// --------------------------------------------------------------------

	public function test_fluent_chaining(): void
	{
		$builder = new TableBuilder('users');
		$result = $builder->id()
			->string('name', 100)
			->string('email', 191)->unique('email')
			->boolean('active')
			->timestamp('created_at');

		$this->assertSame($builder, $result);

		$fields = $builder->getFields();
		$this->assertCount(5, $fields);
		$this->assertEquals(['id'], $builder->getPrimaryKeys());
		$this->assertEquals(['email'], $builder->getUniqueKeys());
	}

	// --------------------------------------------------------------------

	public function test_composite_primary_key(): void
	{
		$builder = new TableBuilder('pivot');
		$builder->integer('user_id', 11, false, false)
			->primary('user_id')
			->integer('role_id', 11, false, false)
			->primary('role_id');

		$this->assertEquals(['user_id', 'role_id'], $builder->getPrimaryKeys());
	}
}
