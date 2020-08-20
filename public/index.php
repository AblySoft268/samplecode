<?php
require_once dirname(__DIR__) . '/conf/conf.php';

require_once dirname(__FILE__) . '/application-top.php';

define ('CONF_FORM_ERROR_DISPLAY_TYPE', Form::FORM_ERROR_TYPE_AFTER_FIELD);
define('CONF_FORM_REQUIRED_STAR_WITH', Form::FORM_REQUIRED_STAR_WITH_CAPTION);
define('CONF_FORM_REQUIRED_STAR_POSITION', Form::FORM_REQUIRED_STAR_POSITION_BEFORE);

FatApp::unregisterGlobals();
FatApplication::getInstance()->callHook();
