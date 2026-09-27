###########################################################
Migrating from CodeIgniter 3 to Customigniter 3
###########################################################

Customigniter 3 keeps the CodeIgniter 3 architecture: the same directory
layout, the same MVC flow, the same configuration files and the same
``CI_*`` class names. Most applications can be moved over without code
changes. This page lists everything that actually differs.

************
Requirements
************

Customigniter 3 requires **PHP 8.4 or newer**. CodeIgniter 3 supported
PHP 5.6 and up, so the first step of any migration is making sure your
host runs a current PHP.

****************
Database drivers
****************

Seven legacy database drivers have been **removed**:

- ``mysql`` (the old ``mysql_*`` extension, removed in PHP 7)
- ``cubrid``, ``mssql``, ``ibase``, ``oci8``, ``odbc``, ``sqlsrv``

The supported drivers are:

- ``mysqli`` and ``pdo`` (MySQL/MariaDB)
- ``postgre`` and ``pdo`` (PostgreSQL)
- ``sqlite3`` and ``pdo`` (SQLite)

Update ``application/config/database.php`` accordingly::

    $db['default'] = array(
        'dbdriver' => 'mysqli',   // was 'mysql'
        // ...
    );

Connections, query builder, Active Record and forge behave the same as
in CodeIgniter 3 for the remaining drivers.

*****************************
Removed compatibility layers
*****************************

- The gutted ``system/core/compat/hash.php``, ``password.php`` and
  ``standard.php`` stubs are gone; PHP has provided those functions and
  constants natively for years. The ``mbstring`` compatibility layer
  remains.
- ``mbstring.func_overload`` handling was dropped (the ini setting was
  removed in PHP 8.0).
- Internal ``is_php()`` runtime version checks were removed from
  ``system/``. The ``is_php()`` helper itself is still defined for
  backward compatibility, but new code should use ``PHP_VERSION_ID``
  instead.

**************************
Stricter typing in system/
**************************

Most of ``system/`` now runs under ``declare(strict_types=1)``. Calls
into framework internals no longer silently coerce scalar types, so
pass real types — ``'0'`` instead of ``0`` for strings, ``''`` instead
of ``null`` for string parameters, and so on. TypeError exceptions now
point straight at the offending call.

***********************
New optional components
***********************

Customigniter adds a typed, namespaced layer under ``src/`` (namespace
``Customigniter\``). It complements the ``CI_*`` classes — nothing is
replaced — and is entirely opt-in:

- PSR-11 service container with auto-wiring
- Typed HTTP request/response builders
- Input sanitizer, CSP header builder, password hasher, file-based
  rate limiter
- Migration runner with a fluent schema builder
- PSR-3 logger bridge, opcache helpers
- CLI kernel behind the ``customigniter`` binary

See the components' docblocks in ``src/`` for details.

**************
Command line
**************

CodeIgniter 3 had no console binary. Customigniter ships one::

    customigniter list           # show all available commands
    customigniter serve          # run the development server
    customigniter cache:clear    # empty the application cache directory

Existing CLI scripts that boot ``index.php`` keep working.

*************
Version label
*************

``CI_VERSION`` reports ``0.0.1`` — the fork's own versioning, which no
longer tracks upstream CodeIgniter 3 releases.

*******************
Migration checklist
*******************

#. Install PHP 8.4+.
#. Replace ``system/`` with the Customigniter version; keep your
   ``application/`` folder as is.
#. Diff ``application/config/`` — set the new ``dbdriver`` if you used a
   removed driver, and review any code calling ``is_php()``.
#. Grep your application for ``mysql_`` function calls, removed drivers
   and legacy shims.
#. Run your application's test suite (Customigniter's own suites run
   under PHPUnit 9.6; static analysis runs PHPStan at level max over
   ``src/``).
#. Optionally adopt the ``Customigniter\`` components and the
   ``customigniter`` console for new code.
