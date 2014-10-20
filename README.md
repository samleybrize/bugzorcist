Bugzorcist - Debugging tools for PHP 5.3+
=========================================

Usage
-----

```php
<?php

// var dump
use Bugzorcist\VarDump\VarDumpHtml;

VarDumpHtml::dump($var);

// render exception
use Bugzorcist\Exception\Renderer\ExceptionRendererHtml;

$exception = new Exception("exception message");
$renderer  = new ExceptionRendererHtml($exception);
$renderer->render();

```

For a quick install with [Composer](https://getcomposer.org/) use:

    $ composer require samleybrize/bugzorcist

Requirements
------------

- PHP 5.3+
- [optional] [FirePHP](https://addons.mozilla.org/fr/firefox/addon/firephp/) Firefox extension for FirePHP based renderers
- [optional] [ncurses](http://pecl.php.net/package/ncurses) PHP extension for Ncurses based renderers

Author
------

This project is authored and maintained by Stephen Berquet.

License
-------

Bugzorcist is licensed under the MIT License - see the `LICENSE` file for details
