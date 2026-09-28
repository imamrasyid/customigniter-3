#########################
Customigniter 3
#########################

.. image:: https://github.com/imamrasyid/customigniter-3/actions/workflows/test-phpunit.yml/badge.svg
    :alt: PHPUnit
    :target: https://github.com/imamrasyid/customigniter-3/actions/workflows/test-phpunit.yml

**Customigniter 3** is a modernized, independently developed fork of
`CodeIgniter 3`_, re-targeted at **PHP 8.4+**. It keeps the framework you
know — the MVC structure, the query builder, the drivers, the user guide —
while stripping out years of pre-PHP-8 compatibility baggage and adding a
typed, modern component layer under ``src/``.

.. _CodeIgniter 3: https://codeigniter.com

***********
Requirements
***********

-  PHP 8.4 or newer
-  The extensions commonly required by CodeIgniter (``mbstring``,
   ``mysqli`` and/or ``pdo_mysql``, ``sqlite3``, ``pgsql`` as needed)
-  Composer (for development: dependencies, tests, static analysis)

************
Installation
************

Copy the repository contents into your web root and point your browser at
the ``index.php`` front controller.

For development::

    git clone https://github.com/imamrasyid/customigniter-3.git
    cd customigniter-3
    composer install

**********
Quickstart
**********

Web
===

Serve the project with PHP's built-in server from the CLI::

    php customigniter serve --port=8080

or point any web server (Apache, nginx + php-fpm, Caddy) at the project
root; ``index.php`` is the front controller, exactly like CodeIgniter 3.

Command line
============

The ``customigniter`` binary is a small console kernel::

    customigniter list           # show all available commands
    customigniter serve          # run the development server
    customigniter cache:clear    # empty the application cache directory

******************************
What's new compared to CodeIgniter 3
******************************

-  Requires PHP 8.4+ (CodeIgniter 3 targeted PHP 5.6+); all internal
   ``is_php()`` runtime version checks and ``mbstring.func_overload``
   handling are gone
-  Legacy database drivers (``mysql``, ``cubrid``, ``mssql``, ``ibase``,
   ``oci8``, ``odbc``, ``sqlsrv``) are **removed**; the supported drivers
   are ``mysqli``, ``pdo`` (MySQL/PostgreSQL/SQLite), ``postgre`` and
   ``sqlite3``
-  Pre-PHP-8 compatibility stubs removed from ``system/core/compat``
   (the gutted ``hash.php``, ``password.php`` and ``standard.php`` shims;
   only the ``mbstring`` layer remains)
-  Modern, typed components under ``src/`` (namespace ``Customigniter\``):
   PSR-11 service container, PSR-3 logger bridge, typed HTTP request and
   response, input sanitizer, CSP header builder, password hasher, file-based
   rate limiter, migration runner with schema builder, opcache helpers and
   a CLI kernel
-  New in the Customigniter phases: a ``.env`` loader with ``env()``, a
   typed event dispatcher bridged to the hook system, exception logging
   with file/line context, a self-contained debug toolbar
   (``DEBUG_TOOLBAR=true``), HTTP testing helpers
   (``Customigniter\Testing\TestCase``), a view engine with layouts,
   sections and components, a file-backed job queue drained by
   ``queue:work``, tagged cache invalidation and a rule-based
   validator (``Customigniter\Validation\Validator``)
-  ``system/`` internals run with ``declare(strict_types=1)`` and PHP 8
   attributes where applicable
-  Static analysis with PHPStan at **level max** over ``src/`` runs in CI

***************
Testing & tools
***************

::

    composer test                          # PHPUnit, full suite
    vendor/bin/phpstan analyse             # PHPStan, level max, src/

The PHPUnit configuration lives in ``tests/phpunit.xml``; driver-specific
suites are under ``tests/travis/``. Continuous integration runs both the
full test matrix (MySQL, PostgreSQL, SQLite — with and without JIT) and
PHPStan on every push.

**********
Security
**********

Please do not report security issues publicly. Use
`GitHub private vulnerability reporting <https://github.com/imamrasyid/customigniter-3/security/advisories/new>`_
for this repository and include a reproduction. You will receive an
acknowledgement as soon as the issue is triaged.

************
Documentation
************

-  `User guide source <user_guide_src/source/>`_
-  `Changelog <user_guide_src/source/changelog.rst>`_
-  `Contributing guide <contributing.md>`_

*******
License
*******

Customigniter 3 is open source software released under the MIT license.
See the `license agreement <user_guide_src/source/license.rst>`_ for
details.

Acknowledgement
===============

Customigniter 3 is a fork of CodeIgniter 3. The original CodeIgniter team
would like to thank EllisLab, all the contributors to the CodeIgniter
project and you, the CodeIgniter user. We continue to build on that work.
