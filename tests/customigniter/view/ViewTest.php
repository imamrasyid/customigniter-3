<?php
declare(strict_types=1);

use Customigniter\View\View;
use Customigniter\View\ViewException;

class ViewTest extends CI_TestCase
{
	private string $views;
	private View $view;

	// --------------------------------------------------------------------

	public function set_up(): void
	{
		$this->views = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'support'.DIRECTORY_SEPARATOR.'views';
		$this->view = new View([$this->views]);
	}

	// --------------------------------------------------------------------

	public function tearDown(): void
	{
		View::reset();
	}

	// --------------------------------------------------------------------

	public function test_render_returns_view_output(): void
	{
		$this->assertSame("Hello, Ada!
", $this->view->render('hello', ['name' => 'Ada']));
	}

	// --------------------------------------------------------------------

	public function test_render_escapes_data_through_e(): void
	{
		$this->assertSame("Hello, Ada &amp; Co!
", $this->view->render('hello', ['name' => 'Ada & Co']));
	}

	// --------------------------------------------------------------------

	public function test_render_appends_php_extension(): void
	{
		$this->assertStringContainsString('Value: 7', $this->view->render('plain', ['value' => 7]));
	}

	// --------------------------------------------------------------------

	public function test_render_accepts_explicit_extension(): void
	{
		$this->assertStringContainsString('Value: 7', $this->view->render('plain.php', ['value' => 7]));
	}

	// --------------------------------------------------------------------

	public function test_render_throws_when_view_is_missing(): void
	{
		$this->expectException(ViewException::class);
		$this->expectExceptionMessage('View not found: nope');

		$this->view->render('nope');
	}

	// --------------------------------------------------------------------

	public function test_render_rejects_parent_traversal(): void
	{
		$this->expectException(ViewException::class);

		$this->view->render('../secret');
	}

	// --------------------------------------------------------------------

	public function test_add_path_prepends_directory(): void
	{
		$alt = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'support'.DIRECTORY_SEPARATOR.'views_alt';
		$this->view->addPath($alt);

		$this->assertSame([$alt, $this->views], $this->view->getPaths());
		$this->assertStringContainsString('Alt view', $this->view->render('alt_only'));
	}

	// --------------------------------------------------------------------

	public function test_layout_wraps_content(): void
	{
		$output = $this->view->render('page_body', ['author' => 'Ada'], ['layout' => 'layout_main']);

		$this->assertSame("<layout>Body by Ada</layout>\n", $output);
	}

	// --------------------------------------------------------------------

	public function test_layout_receives_the_render_data(): void
	{
		$output = $this->view->render('page_body', ['author' => 'Ada', 'title' => 'Home'], ['layout' => 'layout_data']);

		$this->assertStringContainsString('<h1>Home</h1>', $output);
		$this->assertStringContainsString('Body by Ada', $output);
	}

	// --------------------------------------------------------------------

	public function test_custom_section_name_holds_the_content(): void
	{
		$output = $this->view->render('page_body', ['author' => 'Ada'], [
			'layout' => 'layout_full',
			'section' => 'sidebar',
		]);

		$this->assertStringContainsString('<aside>Body by Ada</aside>', $output);
		$this->assertStringContainsString('<main></main>', $output);
	}

	// --------------------------------------------------------------------

	public function test_sections_captured_inside_a_view_reach_the_layout(): void
	{
		$output = $this->view->render('sectioned', [], ['layout' => 'layout_full']);

		$this->assertStringContainsString('<aside>Side bar</aside>', $output);
		$this->assertStringContainsString('<main>Main body', $output);
	}

	// --------------------------------------------------------------------

	public function test_yield_content_returns_default_for_empty_section(): void
	{
		$this->assertSame('fallback', $this->view->yieldContent('nothing', 'fallback'));
	}

	// --------------------------------------------------------------------

	public function test_render_section_is_an_alias(): void
	{
		$this->view->start('quote');
		echo 'AB';
		$this->view->stop();

		$this->assertSame('AB', $this->view->renderSection('quote'));
		$this->assertSame('AB', $this->view->yieldContent('quote'));
	}

	// --------------------------------------------------------------------

	public function test_stop_without_start_throws(): void
	{
		$this->expectException(ViewException::class);

		$this->view->stop();
	}

	// --------------------------------------------------------------------

	public function test_include_inherits_surrounding_data(): void
	{
		$output = $this->view->render('parent_partial', ['who' => 'Ada']);

		$this->assertSame("Parent [Who: Ada] done\n", $output);
	}

	// --------------------------------------------------------------------

	public function test_include_can_opt_out_of_preserve_data(): void
	{
		$output = $this->view->render('parent_partial_nopreserve', ['who' => 'Ada']);

		$this->assertSame("Parent [Who: Bob] done\n", $output);
	}

	// --------------------------------------------------------------------

	public function test_component_exposes_slot(): void
	{
		$output = $this->view->component('component_card', [], '<em>Hi</em>');

		$this->assertStringContainsString('<div class="card"><em>Hi</em></div>', $output);
	}

	// --------------------------------------------------------------------

	public function test_e_escapes_html(): void
	{
		$this->assertSame('&lt;b&gt;&quot;x&quot;&lt;/b&gt;', $this->view->e('<b>"x"</b>'));
	}

	// --------------------------------------------------------------------

	public function test_e_normalises_scalars(): void
	{
		$this->assertSame('', $this->view->e(null));
		$this->assertSame('1', $this->view->e(true));
		$this->assertSame('', $this->view->e(false));
		$this->assertSame('12', $this->view->e(12));
	}

	// --------------------------------------------------------------------

	public function test_e_rejects_arrays(): void
	{
		$this->expectException(ViewException::class);

		$this->view->e(['nope']);
	}

	// --------------------------------------------------------------------

	public function test_shared_instance_is_recreated_after_reset(): void
	{
		$first = View::instance();

		$this->assertSame($first, View::instance());

		View::reset();

		$this->assertNotSame($first, View::instance());
	}
}
