<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'conf-common.php';

define('CONF_APPLICATION_PATH', CONF_INSTALLATION_PATH . 'application/');
define('CONF_THEME_PATH', CONF_APPLICATION_PATH . 'views/');

define('CONF_WEBROOT_URL', 'http://localhost/projects/sample_app/');
define('CONF_WEBROOT_URL_TRADITIONAL', CONF_WEBROOT_URL . 'public/index.php?url=');
