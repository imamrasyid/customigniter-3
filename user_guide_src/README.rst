########################
Customigniter User Guide
########################

******************
Setup Instructions
******************

The Customigniter user guide uses Sphinx to manage the documentation and
output it to various formats.  Pages are written in human-readable
`ReStructured Text <https://www.sphinx-doc.org/en/master/usage/restructuredtext/index.html>`_ format.

Prerequisites
=============

You need Python 3.9 or newer.  You can confirm your version by executing
``python --version`` in a Terminal window.

Installation
============

From the repository root:

1. ``python -m pip install -r user_guide_src/requirements.txt``
2. ``python -m pip install user_guide_src/cilexer`` (the CI Lexer which allows PHP, HTML, CSS, and JavaScript syntax highlighting in code examples; see *cilexer/README*)
3. ``cd user_guide_src``
4. ``make html``

On Windows, or if ``make`` is not available, run step 4 as::

	python -m sphinx -b html source build/html

Editing and Creating Documentation
==================================

All of the source files exist under *source/* and is where you will add new
documentation or modify existing documentation.  Just as with code changes,
we recommend working from feature branches and making pull requests to
the *master* branch of this repo.

So where's the HTML?
====================

Obviously, the HTML documentation is what we care most about, as it is the
primary documentation that our users encounter.  Since revisions to the built
files are not of value, they are not under source control.  This also allows
you to regenerate as necessary if you want to "preview" your work.  Generating
the HTML is very simple.  From the *user_guide_src* directory issue the
command you used at the end of the installation instructions::

	make html

You will see it do a whiz-bang compilation, at which point the fully rendered
user guide and images will be in *build/html/*.  After the HTML has been built,
each successive build will only rebuild files that have changed, saving
considerable time.  If for any reason you want to "reset" your build files,
simply delete the *build* folder's contents and rebuild.

***************
Style Guideline
***************

Please refer to source/documentation/index.rst for general guidelines for
using Sphinx to document Customigniter.
