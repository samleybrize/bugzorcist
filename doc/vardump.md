Bugzorcist - Var dump usage
===========================

Var dump HTML
-------------

```php
<?php

use Bugzorcist\VarDump\VarDumpHtml;

// dumps the var $var with "label" as the title
VarDumpHtml::dump($var, "label");

// dumps the var $var and return the generated HTML instead of printing it
VarDumpHtml::dump($var, "label", true);

// dumps the var $var without the stack trace
VarDumpHtml::dump($var, "label", false, false);
```

Var dump FirePHP
----------------

(Requires the [FirePHP](https://addons.mozilla.org/fr/firefox/addon/firephp/) Firefox extension)

```php
<?php

use Bugzorcist\VarDump\VarDumpFirePhp;

// dumps the var $var into firebug console with "label" as the title
VarDumpFirePhp::dump($var, "label");

// dumps the var $var into firebug console without the stack trace
VarDumpFirePhp::dump($var, "label", false);
```

Var dump Ncurses
----------------

(Requires the [ncurses](http://pecl.php.net/package/ncurses) PHP extension)

Only usable from command line

```php
<?php

use Bugzorcist\VarDump\VarDumpNcurses;

// dumps the var $var with "label" as the title
VarDumpNcurses::dump($var, "label");

// dumps the var $var without the stack trace
VarDumpNcurses::dump($var, "label", false);
```
