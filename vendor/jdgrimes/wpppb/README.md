# WP Plugin PHPUnit Bootstrap [![Build Status](https://travis-ci.org/JDGrimes/wpppb.svg?branch=master)](https://travis-ci.org/JDGrimes/wpppb) [![Latest Stable Version](https://poser.pugx.org/jdgrimes/wpppb/version)](https://packagist.org/packages/jdgrimes/wpppb) [![License](https://poser.pugx.org/jdgrimes/wpppb/license)](https://packagist.org/packages/jdgrimes/wpppb)

Bootstrap for integration testing WordPress plugins with PHPUnit.

## Installation

```bash
composer require --dev jdgrimes/wpppb
```

## Set Up

```bash
vendor/bin/wpppb-init
```

Answer the prompts, and you are ready to go!

(Note that the default bootstrap utilizes Composer's PHP autoloader, which requires
PHP 5.3. See here for [instructions on usage with PHP 5.2](https://github.com/JDGrimes/wpppb/wiki/PHP-5.2).)

## Usage

You can run your PHPUnit tests just as you normally would:

```bash
phpunit
```

You can also do other cool things like [test your plugin's uninstall routine](https://github.com/JDGrimes/wpppb/wiki/Testing-Uninstallation).

## Purpose

The purpose of this project is to provide a bootstrap for plugin developers who want
to perform integration tests for their plugin using WordPress core's testsuite. Its
aim is not only to make this easier, but also better, by providing an implementation
that makes the tests as realistic as possible.

To this end, the loader works by remotely activating the plugin(s), and letting
WordPress load them just as it normally would. This provides more realistic testing
than manually including and activating the plugins on the `muplugins_loaded` action,
as is usually done.

## License

This project's code is provided under the MIT license.
