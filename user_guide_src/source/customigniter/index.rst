###############################################
Customigniter Extensions
###############################################

This chapter documents the components Customigniter 3 adds on top of
CodeIgniter 3. Everything lives under the ``Customigniter\`` namespace
(``src/``, PSR-4 autoloaded via Composer) and is **opt-in**: existing
CodeIgniter applications keep working unchanged.

.. contents::
   :local:
   :depth: 2

*************
Environment
*************

``Customigniter\Core\EnvLoader`` loads a ``.env`` file from the project
root and exposes values through the ``env()`` helper::

    $debug = env('DEBUG_TOOLBAR', false);

Real environment variables always override values defined in the file.
See ``.env.example`` for the supported keys.

******
Events
******

``Customigniter\Events\EventDispatcher`` implements a typed event bus.
CodeIgniter's hook system is bridged onto it, so ``system/core/Hooks``
triggers events through the dispatcher and ``display_override`` and
friends are observable as regular events.

***********
Exceptions
***********

Uncaught exceptions are routed through a dedicated handling chain that
logs the failure with file and line context before falling back to the
framework error views, instead of printing raw output.

***************
Command Line
***************

The ``customigniter`` binary boots a small console kernel::

    customigniter list              # show every available command
    customigniter serve             # development server
    customigniter optimize          # clear compiled caches
    customigniter cache:clear       # empty the cache directory
    customigniter queue:work        # process queued jobs
    customigniter make:controller   # scaffolding (also model, library,
                                   # helper, view, migration, seeder)
    customigniter migrate           # migrate, rollback, status
    customigniter db:seed           # run seeders

*************
Debug Toolbar
*************

When ``DEBUG_TOOLBAR=true`` is set in the environment (or
``$config['debug_toolbar'] = TRUE;``), every page appends a
self-contained HTML panel with request, timing, database and
environment tabs. The panel is built by
``Customigniter\Debug\Toolbar`` on the ``display_override`` event and
never requires a public asset directory.

***************
HTTP Testing
***************

``Customigniter\Testing\TestCase`` dispatches real requests through the
framework and returns a ``TestResponse`` with assertions::

    class PagesTest extends \Customigniter\Testing\TestCase
    {
        public function test_home(): void
        {
            $this->get('/welcome')
                ->assertOk()
                ->assertSee('Welcome');
        }
    }

Supported verbs: ``get``, ``post``, ``put``, ``patch``, ``delete``,
``head`` and ``json`` (JSON body with an ``Accept`` header).

****
View
****

``Customigniter\View\View`` renders PHP views with layouts, sections
and components::

    $html = $view->render('pages/home', $data, ['layout' => 'layouts/main']);

    $html = $view->include('partials/nav', ['active' => 'home']);
    $html = $view->component('alert', ['type' => 'info'], $slot);
    $safe = $view->e($untrusted);

Inside a view file ``$this`` is the engine, so ``$this->start('sidebar')``
/ ``$this->stop()`` capture sections and ``$this->yieldContent('sidebar')``
renders them in the layout. ``renderSection()`` is an alias for
CodeIgniter 4 muscle memory. Missing views and ``..`` traversal raise
``ViewException``.

*****
Queue
*****

Jobs implement ``Customigniter\Queue\JobInterface``::

    class SendReport implements \Customigniter\Queue\JobInterface
    {
        public function handle(array $data): void
        {
            // ...
        }
    }

    $queue->push(SendReport::class, ['id' => 42], $delaySeconds = 60);

``ArrayQueue`` keeps jobs in memory; ``FileQueue`` persists one JSON
file per job under ``CACHE_PATH/queue``. ``Worker`` drains either one,
logging and skipping jobs that are missing, malformed or throwing::

    customigniter queue:work --once
    customigniter queue:work --limit=25

*****
Cache
*****

``Customigniter\Cache\Cache`` wraps a pluggable driver (file-based by
default) and adds tag invalidation::

    $cache = new \Customigniter\Cache\Cache();
    $cache->set('config', $data, $ttlSeconds = 300);
    $cache->get('config');

    $cache->tags(['posts', 'homepage'])->set('latest', $items);
    $cache->tags(['posts'])->flush();   # removes only tagged keys

**********
Validation
**********

``Customigniter\Validation\Validator`` runs pipe or array rules per
field::

    $validator = new \Customigniter\Validation\Validator();
    $validator->setRules([
        'email' => 'required|email',
        'age'   => 'required|integer|min[18]|max[120]',
    ]);
    $validator->setLabels(['email' => 'Email address']);

    if ( ! $validator->run($input))
    {
        $errors = $validator->errors();        # field => [messages]
        $flat   = $validator->errorsString();  # one string
        $clean  = $validator->getValidated();  # passing fields only
    }

Built-in rules: ``required``, ``email``, ``integer``, ``numeric``,
``min_length[n]``, ``max_length[n]``, ``min[n]``, ``max[n]``,
``regex_match[pattern]``, ``in_list[a,b,c]`` and ``equals[field]``.
Messages are customizable per field and rule with ``setMessage()`` and
the ``{field}`` / ``{param}`` placeholders.
