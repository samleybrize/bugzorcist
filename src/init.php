<?php

// in PHP < 5.4, REQUEST_TIME_FLOAT does not exists
if (array_key_exists("REQUEST_TIME_FLOAT", $_SERVER)) {
    $_SERVER["REQUEST_TIME_FLOAT"] = microtime(true);
}
