# Contributing to Customigniter 3

Customigniter 3 is a community driven project and accepts contributions of code and documentation from the community. These contributions are made in the form of Issues or [Pull Requests](https://help.github.com/send-pull-requests/) on the [Customigniter repository](https://github.com/customigniter/customigniter3) on GitHub.

Issues are a quick way to point out a bug. If you find a bug or documentation error in Customigniter then please check a few things first:

1. There is not already an open Issue
2. The issue has already been fixed (check the develop branch, or look for closed Issues)
3. Is it something really obvious that you can fix yourself?

Reporting issues is helpful but an even better approach is to send a Pull Request, which is done by "Forking" the main repository and committing to your own copy. This will require you to use the version control system called Git.

## Guidelines

Before we look into how, here are the guidelines. If your Pull Requests fail
to pass these guidelines it will be declined and you will need to re-submit
when you've made the changes. This might sound a bit tough, but it is required
for us to maintain quality of the code-base.

### PHP Style

Customigniter 3 follows the [CodeIgniter Style Guide](https://codeigniter.com/userguide3/general/styleguide.html) inherited from CodeIgniter 3, with the addition of modern PHP 8.4 features where applicable (typed properties, named arguments, enums, match expressions).

## Development Workflow

To contribute:

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a feature branch: `git checkout -b feature/my-change`
4. Make your changes, following the style guide
5. Run the test suite with PHP 8.4+: `composer test`
6. Commit with `--signoff` and open a Pull Request

The `develop` branch is the working branch. The `master` branch holds the
latest stable release.

## Signing Your Work

You must sign your work, certifying that you either wrote the work or otherwise have the right to pass it on to an open source project. git makes this trivial as you merely have to use `--signoff` on your commits to your Customigniter fork.

## Running the Test Suite

Customigniter ships with a PHPUnit test suite (see `tests/`). It requires
PHP 8.4+:

```bash
composer test
```

The suite covers both the legacy `system/` core and the modern `src/` components.