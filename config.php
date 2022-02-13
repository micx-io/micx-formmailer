<?php

define("DEV_MODE", (bool)"1");

if (DEV_MODE === true) {
    define("CONFIG_PATH", "/opt/cfg");
} else {
    define("CONFIG_PATH", "/data");
}


