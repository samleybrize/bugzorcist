Bugzorcist - Data profiler
==========================

```php
<?php

use Bugzorcist\Profiler\DataProfiler\DataProfiler;

$dataProfiler = new DataProfiler("data source name");

// You can disable the profiler if you want
$dataProfiler->setEnabled(false);

// Starts a query profile
// $queryText can be whatever string you want (a SQL query, a JSON string, ...)
// $params is optional, and can be an array of parameters
// Returns a profile identifier
$identifier = $dataProfiler->startQuery($queryText, $params);

// Stops a query profile
// $identifier is optional. If you omit it, the last started query profile is stopped.
$dataProfiler->stopQuery($identifier);

// You can get a query profile with its identifier
$dataProfiler->getProfile($identifier);

// You can get all profiles too
$dataProfiler->getProfiles();

// You can get the cumulated execution time of all profiles
$dataProfiler->getTotalElapsedSecs();

```

Profile
-------

```php
<?php

$profile = $dataProfiler->getProfile($identifier);

// Indicates if the profile has ended
$profile->hasEnded();

// Returns the query string
$profile->getQueryText();

// Returns query params
$profile->getQueryParams();

// Returns query execution time
$profile->getElapsedSecs();

// Returns the UNIX timestamp (with microseconds) at the start of the profile
$profile->getStartMicrotime();

// Returns the UNIX timestamp (with microseconds) at the end of the profile
$profile->getEndMicrotime();

// Returns the memory usage (in bytes) at the start of the profile.
// $format (boolean) is optional. If it is `true`, the returned values will be a
// human readable string (eg: 745kb).
$profile->getStartMemoryUsage($format);

// Returns the memory usage (in bytes) at the end of the profile.
$profile->getEndMemoryUsage($format);

// Returns the memory peak usage (in bytes) at the start of the profile.
$profile->getStartMemoryPeakUsage($format);

// Returns the memory peak usage (in bytes) at the end of the profile.
$profile->getEndMemoryPeakUsage($format);

```

Query formatter
---------------

By default, a data profiler has a default formatter which does nothing particular. You can replace it with another formatter as long it implements the `\Bugzorcist\Profiler\DataProfiler\Formatter\FormatterInterface` interface.

```php
<?php

use Bugzorcist\Profiler\DataProfiler\Formatter\FormatterSql;

$formatter = new FormatterSql();
$dataProfiler->setFormatter($formatter);

...

$profile             = $dataProfiler->getProfile($identifier);

// format as HTML
$formattedQueryHtml  = $dataProfiler->getFormatter()->formatHtml($profile->getQueryText());

// format as plain text
$formattedQueryPlain = $dataProfiler->getFormatter()->formatPlain($profile->getQueryText());

```

Data profiler manager
----------------

A data profiler manager can hold one or more data profilers. It is usefull to pass a list of data profilers as an argument.

```php
<?php

use Bugzorcist\Profiler\DataProfiler\DataProfiler;
use Bugzorcist\Profiler\DataProfiler\DataProfilerManager;

$dataProfiler        = new DataProfiler("data source name");

$dataProfilerManager = new DataProfilerManager();
$dataProfilerManager->addProfiler($dataProfiler);

```

Profiler proxies
----------------

### Doctrine

`ProxyDoctrine` implements the `\Doctrine\DBAL\Logging\SQLLogger` interface so it can be used as a Doctrine profiler.

```php
<?php

use Bugzorcist\Profiler\DataProfiler\DataProfiler;
use Bugzorcist\Profiler\DataProfiler\ProfilerProxy\ProxyDoctrine;

$dataProfiler     = new DataProfiler("data source name");
$doctrineProfiler = new ProxyDoctrine($dataProfiler);

```

### PDO

`PDOProfiler` extends `PDO` so it can be used instead of a `PDO` object, and add profiler functionnalities.

```php
<?php

use Bugzorcist\Profiler\DataProfiler\DataProfiler;
use Bugzorcist\Profiler\DataProfiler\ProfilerProxy\PDO\PDOProfiler;

$pdo          = new PDO($dsn);
$dataProfiler = new DataProfiler("data source name");
$pdoProxy     = new PDOProfiler($pdo, $dataProfiler);

$pdoProxy->exec("SELECT * FROM table");

```
