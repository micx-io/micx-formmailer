<?php

define("DEV_MODE", (bool)"1");

define("CONF_SUBSCRIPTION_ENDPOINT", "/opt/mock/sub");
define("CONF_SUBSCRIPTION_CLIENT_ID", "micx-formmailer");
define("CONF_SUBSCRIPTION_CLIENT_SECRET", "");

define("CONF_SMTP_SENDER_HOST", "");

if (DEV_MODE === true) {
    define("CONFIG_PATH", "/opt/cfg");
} else {
    define("CONFIG_PATH", "/config");
}


