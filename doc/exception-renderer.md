Bugzorcist - Exception renderer usage
=====================================

General
-------

```php
<?php

$renderer  = ...

// Optional
// You can set the configuration of tour application to render it
// This can be whatever you want
$renderer->setApplicationConfig($config);

// Optional
// You can set a profiler manager that holds one or more profilers
// Must be an instance of \Bugzorcist\Profiler\Profiler\ProfilerManager
$renderer->setProfilerManager($profilerManager);

// Optional
// You can set a data profiler manager that holds one or more data profilers
// Must be an instance of \Bugzorcist\Profiler\DataProfiler\DataProfilerManager
$renderer->setDataProfilerManager($dataProfilerManager);

$renderer->render();

```

Exception handler
-----------------

```php
<?php

use Bugzorcist\Exception\Renderer\ExceptionHandler;

$handler = new ExceptionHandler();

// Optional
// You can set the configuration of tour application to render it.
// This can be whatever you want.
$handler->setApplicationConfig($config);

// Optional
// You can set a profiler manager that holds one or more profilers.
// Must be an instance of \Bugzorcist\Profiler\Profiler\ProfilerManager
$handler->setProfilerManager($profilerManager);

// Optional
// You can set a data profiler manager that holds one or more data profilers.
// Must be an instance of \Bugzorcist\Profiler\DataProfiler\DataProfilerManager
$handler->setDataProfilerManager($dataProfilerManager);

// Registers as an exception handler.
// Any uncaught exception will be handled by this handler and rendered.
// The renderer is automatically chosen depending on the context
// (if it is an AJAX call, the Firephp renderer is used, if you are in command line the
// Ncurses renderer is called, ...).
// If you have previously defined an exception handler with "set_exception_handler()", it will be called
// after the rendering.
$handler->registerHandler();

// You can indicate to the handler to render a dummy exception at the end of the PHP execution,
// even if no exception has been thrown. This is usefull to obtain informations about execution time,
// memory usage, ... for a normal execution
$handler->registerShutdown();

```

HTML renderer
-------------

```php
<?php

use Bugzorcist\Exception\Renderer\ExceptionRendererHtml;

$exception = new Exception("exception message");
$renderer  = new ExceptionRendererHtml($exception);

$renderer->render();

```

FirePHP renderer
-------------

(Requires the [FirePHP](https://addons.mozilla.org/fr/firefox/addon/firephp/) Firefox extension)

Renders into the firebug console.

```php
<?php

use Bugzorcist\Exception\Renderer\ExceptionRendererFirephp;

$exception = new Exception("exception message");
$renderer  = new ExceptionRendererFirephp($exception);

$renderer->render();

```

Ncurses renderer
-------------

(Requires the [ncurses](http://pecl.php.net/package/ncurses) PHP extension)

Only usable from command line.

```php
<?php

use Bugzorcist\Exception\Renderer\ExceptionRendererNcurses;

$exception = new Exception("exception message");
$renderer  = new ExceptionRendererNcurses($exception);

$renderer->render();

```

CLI renderer
-------------

Minimalist renderer for command line applications. Also used as a fallback renderer if the `ncurses` PHP extension is not available.

```php
<?php

use Bugzorcist\Exception\Renderer\ExceptionRendererCli;

$exception = new Exception("exception message");
$renderer  = new ExceptionRendererCli($exception);

$renderer->render();

```
