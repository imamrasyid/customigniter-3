#########################
What is Customigniter 3
#########################

Customigniter 3 is a modernized fork of CodeIgniter 3, an Application
Development Framework - a toolkit - for people who build web sites using
PHP. Its goal is to enable you to develop projects much faster than you
could if you were writing code from scratch, by providing a rich set of
libraries for commonly needed tasks, as well as a simple interface and
logical structure to access these libraries. Customigniter lets you
creatively focus on your project by minimizing the amount of code needed
for a given task.

***************
Customigniter 3
***************

This repository is a modernized continuation of the legacy CodeIgniter 3
framework, re-targeted for **PHP 8.4+**. It strips out pre-PHP-8
compatibility layers (``is_php()`` checks, mbstring.func_overload, legacy
database drivers), adds typed modern components under ``src/`` (PSR-11
service container, migrations, security helpers, console), and is developed
independently as its own project.

********************************
Differences from CodeIgniter 3
********************************

- Requires PHP 8.4+ (CodeIgniter 3 targeted PHP 5.6+)
- Removed all internal ``is_php()`` runtime version checks
- Removed ``mbstring.func_overload`` handling (removed in PHP 8.0)
- Legacy database drivers (mysql, cubrid, mssql, ibase, oci8, odbc) emit
  ``E_USER_DEPRECATED``; use ``mysqli`` or ``pdo``
- Added ``src/`` namespace with a PSR-11 service container, typed HTTP
  request/response, password hashing, CSP builder, rate limiter, migrations
  and a CLI kernel
- Modernized ``system/`` internals with ``declare(strict_types=1)`` and
  PHP 8 attributes where applicable

*******************
Release Information
*******************

This repo contains in-development code for future releases.

**************************
Changelog and New Features
**************************

You can find a list of all changes for each release in the `user
guide change log <user_guide_src/source/changelog.rst>`_.

*******************
Server Requirements
*******************

PHP version 8.4 or newer is required.

****************
Installation
****************

Copy the repository contents into your web root and point your browser at
the ``index.php`` front controller.

*******
License
*******

Please see the `license agreement <user_guide_src/source/license.rst>`_.

*********
Resources
*********

-  `User Guide <user_guide_src/source/>`_
-  `Contributing Guide <contributing.md>`_

Acknowledgement
===============

Customigniter 3 is a fork of CodeIgniter 3. The original CodeIgniter team
would like to thank EllisLab, all the contributors to the CodeIgniter
project and you, the CodeIgniter user. We continue to build on that work.