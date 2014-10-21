Bugzorcist - Profiler
==========================

```php
<?php

use Bugzorcist\Profiler\Profiler\Profiler;

$profiler = new Profiler("data source name");

// You can disable the profiler if you want
$profiler->setEnabled(false);

// Starts a profile
// $params is optional, and can be an array of parameters
// $comment is optional, and can be any string you want
// Returns a profile identifier
$identifier = $profiler->startProfile($params, $comment);

// Stops a profile
// $identifier is optional. If you omit it, the last started profile is stopped.
$profiler->stopProfile($identifier);

// You can get a profile with its identifier
$profiler->getProfile($identifier);

// You can get all profiles too
$profiler->getProfiles();

// You can get the cumulated execution time of all profiles
$profiler->getTotalElapsedSecs();

```

Profile
-------

```php
<?php

$profile = $profiler->getProfile($identifier);

// Indicates if the profile has ended
$profile->hasEnded();

// Returns the function name, file, line and stack trace from where the profile was started
$profile->getCallingFunction();
$profile->getCallingFile();
$profile->getCallingLine();
$profile->getCallingTrace();

// Returns params
$profile->getParams();

// Returns comment
$profile->getComment();

// Returns profile execution time
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

Profiler manager
----------------

A profiler manager can hold one or more profilers. It is usefull to pass a list of profilers as an argument.

```php
<?php

use Bugzorcist\Profiler\Profiler\Profiler;
use Bugzorcist\Profiler\Profiler\ProfilerManager;

$profiler        = new Profiler("data source name");

$profilerManager = new ProfilerManager();
$profilerManager->addProfiler($profiler);

```
